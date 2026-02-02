<?php

class EvoSessionProxy
{
    private static bool $initialized = false;
    private static bool $synced = false;
    private static bool $shutdownRegistered = false;

    private static ?object $laravelStore = null;
    private static bool $storeResolved = false;

    /**
     * Early init - before Laravel middleware.
     * Ensure $_SESSION is an array (do NOT overwrite if already initialized).
     */
    public static function earlyInit(): void
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }

        $createdKey = 'evo.session.created.time';
        if (!isset($_SESSION[$createdKey])) {
            $_SESSION[$createdKey] = $_SERVER['REQUEST_TIME'] ?? time();
        }
    }

    /**
     * One-time initialization of the session proxy.
     * - Starts native PHP session, using Laravel cookie ID if available
     * - Loads Laravel session (ensuring it is started, syncing ID if needed)
     * - Migrates legacy EVOSESSID if needed
     * - Merges data: Laravel wins on conflicts
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        $cookieName = self::getLaravelSessionCookieName();
        $cookieId = self::getCookieValue($cookieName);
        if (is_string($cookieId) && $cookieId !== '') {
            session_id($cookieId);
        }

        @session_start();

        // Capture any early data
        $earlyData = is_array($_SESSION) ? $_SESSION : [];
        $_SESSION = $earlyData;

        $store = self::getLaravelSessionStore();
        if ($store === null) {
            self::$initialized = true;
            return;
        }

        self::ensureLaravelSessionStarted($store);
        self::migrateLegacySessionIfNeeded($store);

        // Laravel → $_SESSION (Laravel wins on conflicts).
        $laravelData = $store->all();
        foreach ($laravelData as $key => $value) {
            $_SESSION[$key] = $value;
        }

        // Merge back early data for keys not present in Laravel.
        foreach ($earlyData as $key => $value) {
            if (!array_key_exists($key, $laravelData)) {
                $_SESSION[$key] = $value;
                $store->put($key, $value);
            }
        }

        self::$initialized = true;

        if (!self::$shutdownRegistered) {
            self::$shutdownRegistered = true;
            register_shutdown_function([self::class, 'syncBack']);
        }
    }

    /**
     * Sync back - before response.
     */
    public static function syncBack(): void
    {
        if (!self::$initialized || self::$synced) {
            return;
        }

        self::$synced = true;

        $store = self::getLaravelSessionStore();
        if ($store === null) {
            return;
        }

        self::ensureLaravelSessionStarted($store);

        $laravelData = $store->all();

        // Filter out internal keys
        $sessionFiltered = [];
        foreach ($_SESSION as $key => $value) {
            if (self::isInternalKey($key)) {
                continue;
            }
            $sessionFiltered[$key] = $value;
        }

        $laravelFiltered = [];
        foreach ($laravelData as $key => $value) {
            if (self::isInternalKey($key)) {
                continue;
            }
            $laravelFiltered[$key] = $value;
        }

        // Forget keys in Laravel but not in $_SESSION
        $toForget = array_diff_key($laravelFiltered, $sessionFiltered);
        foreach ($toForget as $key => $value) {
            $store->forget($key);
        }

        // Put/update keys from $_SESSION that are new or changed
        foreach ($sessionFiltered as $key => $value) {
            if (!array_key_exists($key, $laravelFiltered) || $laravelFiltered[$key] !== $value) {
                $store->put($key, $value);
            }
        }

        $store->save();
    }

    /**
     * Cached resolver for Laravel session store (once per request).
     * @return object|null
     */
    private static function getLaravelSessionStore(): ?object
    {
        if (self::$storeResolved) {
            return self::$laravelStore;
        }

        self::$storeResolved = true;

        if (!function_exists('app')) {
            self::$laravelStore = null;
            return null;
        }

        $app = app();
        if (!is_object($app) || !method_exists($app, 'has') || !$app->has('session')) {
            self::$laravelStore = null;
            return null;
        }

        try {
            $manager = app('session');
        } catch (\Throwable $exception) {
            self::$laravelStore = null;
            return null;
        }

        $store = method_exists($manager, 'driver') ? $manager->driver() : $manager;

        if (!is_object($store) || !method_exists($store, 'all') || !method_exists($store, 'getId')) {
            self::$laravelStore = null;
            return null;
        }

        self::$laravelStore = $store;
        return $store;
    }

    /**
     * @param object $store
     * @return void
     */
    private static function ensureLaravelSessionStarted($store): void
    {
        if (method_exists($store, 'isStarted') && $store->isStarted()) {
            return;
        }

        $cookieName = self::getLaravelSessionCookieName();
        $cookieId = self::getCookieValue($cookieName);
        if (!is_string($cookieId) || $cookieId === '') {
            $cookieId = session_id();
        }
        if ($cookieId && method_exists($store, 'setId')) {
            $store->setId($cookieId);
        }

        if (method_exists($store, 'start')) {
            $store->start();
        }
    }

    /**
     * @param object $store
     * @return void
     */
    private static function migrateLegacySessionIfNeeded($store): void
    {
        $laravelCookie = self::getLaravelSessionCookieName();
        if (!empty($_COOKIE[$laravelCookie])) {
            return;
        }

        $legacyCookie = defined('SESSION_COOKIE_NAME') ? SESSION_COOKIE_NAME : 'EVOSESSID';
        $legacyId = self::getCookieValue($legacyCookie);
        if (!is_string($legacyId) || $legacyId === '') {
            return;
        }

        $payload = self::readLegacySessionPayload($legacyId);
        if ($payload === null || $payload === '') {
            return;
        }

        $legacyData = self::decodeSessionPayload($payload);
        if (!is_array($legacyData) || $legacyData === []) {
            return;
        }

        $existing = $store->all();
        foreach ($legacyData as $key => $value) {
            if (!array_key_exists($key, $existing)) {
                $store->put($key, $value);
            }
        }
        $store->save();

        // Expire legacy cookie after successful migration.
        setcookie($legacyCookie, '', time() - 3600, '/');
        unset($_COOKIE[$legacyCookie]);
    }

    /**
     * @param string $sessionId
     * @return string|null
     */
    private static function readLegacySessionPayload(string $sessionId): ?string
    {
        $savePath = session_save_path();
        if (!is_string($savePath) || $savePath === '') {
            $savePath = sys_get_temp_dir();
        }

        $parts = explode(';', $savePath);
        $path = end($parts);
        if (!is_string($path) || $path === '') {
            return null;
        }

        $file = rtrim($path, "/\\") . DIRECTORY_SEPARATOR . 'sess_' . $sessionId;
        if (!is_readable($file)) {
            return null;
        }

        $payload = file_get_contents($file);
        return ($payload === false) ? null : $payload;
    }

    /**
     * @param string $payload
     * @return array
     */
    private static function decodeSessionPayload(string $payload): array
    {
        $backup = $_SESSION ?? null;
        $_SESSION = [];
        $ok = @session_decode($payload);
        $decoded = ($ok === false) ? [] : $_SESSION;

        if ($backup === null) {
            unset($_SESSION);
        } else {
            $_SESSION = $backup;
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return string
     */
    private static function getLaravelSessionCookieName(): string
    {
        if (function_exists('config')) {
            return (string)config('session.cookie', 'evo_session');
        }
        return 'evo_session';
    }

    /**
     * @param string $name
     * @return string|null
     */
    private static function getCookieValue(string $name): ?string
    {
        if (!isset($_COOKIE[$name])) {
            return null;
        }
        $value = $_COOKIE[$name];
        return is_string($value) ? $value : null;
    }

    /**
     * @param string $key
     * @return bool
     */
    private static function isInternalKey(string $key): bool
    {
        return strncmp($key, '_', 1) === 0;
    }
}
