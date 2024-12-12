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

use Configuration;
use DateTime;
use Db;
use DbQuery;
use Employee;
use PrestaShopException;
use Thirtybees\Module\POS\Auth\Model\Role;
use Thirtybees\Module\POS\Auth\Model\Token;
use Thirtybees\Module\POS\Auth\Model\User;
use Thirtybees\Module\POS\Exception\AccessDeniedException;
use Thirtybees\Module\POS\Exception\InvalidArgumentException;
use Thirtybees\Module\POS\Exception\NotFoundException;
use Thirtybees\Module\POS\Exception\ServerErrorException;
use Thirtybees\Module\POS\OrderProcess\Model\OrderProcess;
use Thirtybees\Module\POS\Workstation\Model\Workstation;
use Tools;
use Validate;

class AuthServiceImpl implements AuthService
{

    /**
     *
     * @param string $username
     * @param string $password
     * @param string $role
     * @param Workstation $workstation
     * @return User
     *
     * @throws AccessDeniedException
     * @throws PrestaShopException
     */
    public function login(string $username, string $password, string $role, Workstation $workstation): User
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
            throw new AccessDeniedException("Employee does not have '$role' role");
        }

        $token = $this->generateToken(
            $employeeId,
            $role,
            $workstation->getId(),
            0,
            3600
        );

        return new User($employee, $token);
    }

    /**
     * @param string $value
     * @return Token|null
     *
     * @throws PrestaShopException
     */
    public function findToken(string $value): ?Token
    {
        $sql = (new DbQuery)
            ->select('t.*')
            ->from('tbpos_token', 't')
            ->innerJoin('employee', 'e', '(e.id_employee = t.id_employee)')
            ->where('t.value = "' . pSQL($value) . '"')
            ->where('e.active')
            ->where('t.expiration > UNIX_TIMESTAMP()');
        $row = Db::getInstance()->getRow($sql);
        if ($row === false) {
            return null;
        }
        return new Token(
            (int)$row['id_tbpos_token'],
            $value,
            (int)$row['id_employee'],
            (int)$row['id_tbpos_workstation'],
            (string)$row['role'],
            (int)$row['id_tbpos_order_process'],
            static::getDateTime((int)$row['generated']),
            static::getDateTime((int)$row['expiration'])
        );
    }

    /**
     * @param int $tokenId
     *
     * @return Token
     *
     * @throws NotFoundException
     * @throws PrestaShopException
     */
    public function getTokenById(int $tokenId): Token
    {
        $sql = (new DbQuery)
            ->select('t.*')
            ->from('tbpos_token', 't')
            ->innerJoin('employee', 'e', '(e.id_employee = t.id_employee)')
            ->where('t.id_tbpos_token = ' .$tokenId );

        $row = Db::getInstance()->getRow($sql);
        if ($row === false) {
            throw new NotFoundException("Token with id $tokenId not found");
        }
        return new Token(
            (int)$row['id_tbpos_token'],
            (string)$row['value'],
            (int)$row['id_employee'],
            (int)$row['id_tbpos_workstation'],
            (string)$row['role'],
            (int)$row['id_tbpos_order_process'],
            static::getDateTime((int)$row['generated']),
            static::getDateTime((int)$row['expiration'])
        );
    }

    /**
     * @param Token $token
     *
     * @return Token
     * @throws PrestaShopException
     */
    public function exchangeToken(Token $token): Token
    {
        $newToken = $this->generateToken(
            $token->getEmployeeId(),
            $token->getRole(),
            $token->getWorkstationId(),
            $token->getOrderProcessId()
        );
        $this->revoke($token);
        return $newToken;
    }

    /**
     * @param Token $token
     * @return User
     *
     * @throws PrestaShopException
     */
    public function tokenIntrospection(Token $token): User
    {
        $employee = new Employee($token->getEmployeeId());
        if (! Validate::isLoadedObject($employee)) {
            throw new ServerErrorException("Employee not found");
        }
        return new User($employee, $token);
    }

    /**
     * @param Token $token
     *
     * @return Token
     * @throws PrestaShopException
     */
    public function revoke(Token $token): Token
    {
        $expiration = new DateTime();
        Db::getInstance()->update('tbpos_token', [
            'id_tbpos_order_process' => 0,
            'expiration' => $expiration->getTimestamp(),
        ], 'id_tbpos_token = ' . $token->getId());

        return $token
            ->setOrderProcessId(0)
            ->setExpiration($expiration);
    }

    /**
     * @param Token $token
     * @param OrderProcess $orderProcess
     * @return Token
     * @throws PrestaShopException
     */
    public function updateTokenProcess(Token $token, OrderProcess $orderProcess): Token
    {
        Db::getInstance()->update('tbpos_token', [
            'id_tbpos_order_process' => $orderProcess->getId()
        ], 'id_tbpos_token = ' . $token->getId());

        return $token->setOrderProcessId($orderProcess->getId());
    }

    /**
     * @param Token $token
     * @return Token
     * @throws PrestaShopException
     */
    public function unsetTokenProcess(Token $token): Token
    {
        Db::getInstance()->update('tbpos_token', [
            'id_tbpos_order_process' => 0,
        ], 'id_tbpos_token = ' . $token->getId());

        return $token->setOrderProcessId(0);
    }

    /**
     * @return string
     * @throws PrestaShopException
     */
    protected function generateTokenValue(): string
    {
        while (true) {
            $value = Tools::passwdGen(32);
            $sql = (new DbQuery())
                ->select('1')
                ->from('tbpos_token')
                ->where('value = "' . pSQL($value) . '"');
            if (Db::getInstance()->getValue($sql) === false) {
                return $value;
            }
        }
    }

    /**
     * @param int $employeeId
     * @param string $role
     * @param int $workstationId
     * @param int $orderProcessId
     *
     * @return Token
     *
     * @throws PrestaShopException
     */
    private function generateToken(int $employeeId, string $role, int $workstationId, int $orderProcessId): Token
    {
        if (! Role::isValidRole($role)) {
            throw new InvalidArgumentException('Invalid role: ' . $role);
        }
        $conn = Db::getInstance();
        $generated = time();
        $exp = (int)Configuration::getGlobalValue('TBPOS_TOKEN_EXPIRATION');
        if ($exp <= 0) {
            $exp = 3600;
        }
        $expiration = $generated + $exp;

        $value = $this->generateTokenValue();
        $result = $conn->insert('tbpos_token', [
            'id_employee' => (int)$employeeId,
            'value' => pSQL($value),
            'role' => pSQL($role),
            'id_tbpos_workstation' => (int)$workstationId,
            'id_tbpos_order_process' => (int)$orderProcessId,
            'generated' => $generated,
            'expiration' => $expiration,
        ]);

        if (! $result) {
            throw new ServerErrorException("Failed to generate token");
        }

        return new Token(
            (int)$conn->Insert_ID(),
            $value,
            $employeeId,
            $workstationId,
            $role,
            $orderProcessId,
            static::getDateTime($generated),
            static::getDateTime($expiration)
        );
    }

    /**
     * @param int $ts
     * @return DateTime
     */
    private static function getDateTime(int $ts)
    {
        $datetime = new DateTime();
        $datetime->setTimestamp($ts);
        return $datetime;
    }

}