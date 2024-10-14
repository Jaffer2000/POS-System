<?php

namespace Thirtybees\Module\POS\Auth\Model;

use ReflectionClass;
use Throwable;

class Role
{
    const ROLE_ADMIN = 'admin';
    const ROLE_CASHIER = 'cashier';

    /**
     * @return string[]
     */
    public static function getRoles(): array
    {
        static $roles = null;
        if (is_null($roles)) {
            $roles = [];
            try {
                // delete everything that starts with SETTINGS_*
                $reflection = new ReflectionClass(static::class);
                foreach ($reflection->getConstants() as $key => $value) {
                    if (strpos($key, "ROLE_") === 0) {
                        $roles[] = $value;
                    }
                }
            } catch (Throwable $ignored) {}
        }
        return $roles;
    }

    /**
     * @param string $role
     *
     * @return bool
     */
    public static function isValidRole(string $role): bool
    {
        return in_array($role, static::getRoles());
    }
}