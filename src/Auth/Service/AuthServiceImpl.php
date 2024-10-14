<?php
/**
 * Copyright (C) 2022-2022 thirty bees <contact@thirtybees.com>
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Thirty Bees Regular License version 1.0
 * For more information see LICENSE.txt file
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2022-2022 Petr Hucik
 * @license   Licensed under the Thirty Bees Regular License version 1.0
 */

namespace Thirtybees\Module\POS\Auth\Service;

use Db;
use DbQuery;
use Employee;
use PrestaShopException;
use Thirtybees\Module\POS\Auth\Model\Role;
use Thirtybees\Module\POS\Auth\Model\Token;
use Thirtybees\Module\POS\Auth\Model\User;
use Thirtybees\Module\POS\Exception\AccessDeniedException;
use Thirtybees\Module\POS\Exception\InvalidArgumentException;
use Thirtybees\Module\POS\Exception\UnauthorizedException;
use Validate;

class AuthServiceImpl implements AuthService
{

    /**
     * @param string $username
     * @param string $password
     * @param string $role
     *
     * @return User
     *
     * @throws AccessDeniedException
     * @throws PrestaShopException
     * @throws UnauthorizedException
     */
    public function login(string $username, string $password, string $role): User
    {
        if (! Role::isValidRole($role)) {
            throw new InvalidArgumentException("Invalid role: $role");

        }
        $employee = new Employee();
        if (!$employee->getByEmail($username, $password, true)) {
            throw new AccessDeniedException("Invalid username or password");
        }
        $employeeId = (int)$employee->id;
        $sql = (new DbQuery())
            ->select('role')
            ->from('tbpos_employee_role')
            ->where('id_employee = ' . $employeeId)
            ->where('role = "' . pSQL($role) . '"');
        $conn = Db::getInstance();
        $roles = $conn->getArray($sql);
        if (!$roles) {
            throw new UnauthorizedException("Employee does not have '$role' role");
        }

        $token = Token::generateToken($employeeId, $role);

        return new User($employee, $token);
    }

    /**
     * @param Token $token
     *
     * @return Token
     * @throws PrestaShopException
     */
    public function exchangeToken(Token $token): Token
    {
        $newToken = Token::generateToken(
            $token->getEmployeeId(),
            $token->getRole(),
            $token->getCartId()
        );
        $token->revoke();
        return $newToken;
    }

    /**
     * @param Token $token
     * @return User
     *
     * @throws AccessDeniedException
     * @throws PrestaShopException
     */
    public function tokenIntrospection(Token $token): User
    {
        $employee = new Employee($token->getEmployeeId());
        if (! Validate::isLoadedObject($employee)) {
            throw new AccessDeniedException("Employee not found");
        }
        return new User($employee, $token);
    }

}