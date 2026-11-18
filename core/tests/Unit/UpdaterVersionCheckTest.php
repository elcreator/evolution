<?php

/*
| The version line of the manager header asks the Updater plugin for the newest version on a click,
| past the cache the dashboard widget keeps (releases once a day, a branch head once an hour).
*/

$root = dirname(__DIR__, 3);

function loadUpdaterPluginFunction(string $name): void
{
    if (function_exists($name)) {
        return;
    }

    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/assets/plugins/updater/plugin.updater.php');
    $start = strpos($source, 'function ' . $name . '(');
    $depth = 0;
    for ($i = strpos($source, '{', $start); $i < strlen($source); $i++) {
        $depth += $source[$i] === '{' ? 1 : ($source[$i] === '}' ? -1 : 0);
        if ($depth === 0) {
            break;
        }
    }
    eval(substr($source, $start, $i - $start + 1));
}

beforeEach(function () {
    loadUpdaterPluginFunction('updaterForceFreshCheck');
    $this->dir = sys_get_temp_dir() . '/updater-check-' . bin2hex(random_bytes(4));
    mkdir($this->dir);
});

afterEach(function () {
    array_map('unlink', glob($this->dir . '/*') ?: []);
    @rmdir($this->dir);
});

test('a forced check drops the cached release list and branch heads, nothing else', function () {
    foreach (['check_18.json', 'check_17.json', 'head_abc_2026111812.json', 'keep.json'] as $file) {
        file_put_contents($this->dir . '/' . $file, '{}');
    }

    expect(updaterForceFreshCheck(30, $this->dir))->toBeTrue()
        ->and(array_map('basename', glob($this->dir . '/*')))->toEqualCanonicalizing(['forced_check.json', 'keep.json']);
});

test('clicks within the interval read what the previous check fetched', function () {
    expect(updaterForceFreshCheck(30, $this->dir))->toBeTrue();
    file_put_contents($this->dir . '/check_18.json', '{"version":"3.5.10"}');

    expect(updaterForceFreshCheck(30, $this->dir))->toBeFalse()
        ->and(is_file($this->dir . '/check_18.json'))->toBeTrue();

    touch($this->dir . '/forced_check.json', time() - 31);
    expect(updaterForceFreshCheck(30, $this->dir))->toBeTrue()
        ->and(is_file($this->dir . '/check_18.json'))->toBeFalse();
});

test('the plugin answers the check before the dashboard renders, with the widget visibility', function () use ($root) {
    $source = (string) file_get_contents($root . '/assets/plugins/updater/plugin.updater.php');
    $endpoint = substr($source, strpos($source, "if (isset(\$_POST['updater_check'])"), 1400);

    expect($endpoint)->toContain("\$modx->Event->name === 'OnManagerPageInit'")
        ->toContain("(\$role != 1 && \$wdgVisibility == 'AdminOnly')")
        ->toContain("(\$user != \$ThisUser && \$wdgVisibility == 'ThisUserOnly')")
        ->and(strpos($source, "if (isset(\$_POST['updater_check'])"))
        ->toBeLessThan(strpos($source, "if (\$role != 1 && \$wdgVisibility == 'AdminOnly') {"))
        // the widget and the check read the same lookup
        ->and($source)->toContain('$latest = updaterResolveLatest($currentVersion, $version, $type, $branch,')
        ->and(substr($source, strpos($source, 'function updaterHandleVersionCheck('), 1600))
        ->toContain('updaterForceFreshCheck();')
        ->toContain('$latest = updaterResolveLatest($currentVersion, $version, $type, $branch, $stableOnly);');
});

test('the header version line is a check button only while the Updater plugin listens', function () use ($root) {
    $frame = (string) file_get_contents($root . '/manager/views/frame/1.blade.php');
    // 3.6 renders the header menu from its own partial
    $topbar = (string) file_get_contents($root . '/manager/views/frame/topbar-actions.blade.php');

    expect($topbar)->toContain("\$versionCheck = in_array('Updater', evo()->pluginEvent['OnManagerPageInit'] ?? [], true);")
        ->toContain("\$versionTag = \$versionCheck ? 'a href=\"javascript:;\" data-version-check' : 'span';")
        ->and($frame)->toContain("<script src=\"@revision(MGR_DIR . '/media/script/version-check-helper.js')\"></script>");
});

test('the header check posts with the CSRF token and shows the answer', function () use ($root) {
    $node = (new Symfony\Component\Process\ExecutableFinder())->find('node');
    if ($node === null) {
        $this->markTestSkipped('Node.js is not installed.');
    }

    $test = $root . '/manager/media/script/tests/version-check-helper.test.js';
    $output = evoRunCommand(escapeshellarg($node) . ' --test ' . escapeshellarg($test) . ' 2>&1', $exitCode);

    expect($exitCode)->toBe(0, $output);
});
