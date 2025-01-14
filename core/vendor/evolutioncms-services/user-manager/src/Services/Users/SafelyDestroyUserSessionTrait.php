<?php

namespace EvolutionCMS\UserManager\Services\Users;

trait SafelyDestroyUserSessionTrait
{
    private $userSessionFields = [
        'Shortname',
        'Fullname',
        'Email',
        'Validated',
        'InternalKey',
        'Failedlogins',
        'Lastlogin',
        'Logincount',
        'Role',
        'Permissions',
        'Docgroups',
        'Token',
    ];

    protected function safelyDestroyUserSession()
    {
        if (defined('NO_SESSION') && !(defined('IN_MANAGER_MODE') && IN_MANAGER_MODE)) {
            return;
        }

        foreach ($this->userSessionFields as $field) {
            unset($_SESSION[$this->context . $field]);
        }
    }
}
