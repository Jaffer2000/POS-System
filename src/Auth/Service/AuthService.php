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


use PrestaShopException;
use Thirtybees\Module\POS\Auth\Model\Token;
use Thirtybees\Module\POS\Auth\Model\User;
use Thirtybees\Module\POS\Exception\AccessDeniedException;
use Thirtybees\Module\POS\Exception\UnauthorizedException;
use Thirtybees\Module\POS\OrderProcess\Model\OrderProcess;
use Thirtybees\Module\POS\Workstation\Model\Workstation;

interface AuthService
{
    /**
     * @param string $username
     * @param string $password
     * @param string $role
     * @param Workstation $workstation
     *
     * @return User
     *
     * @throws AccessDeniedException
     */
    public function login(string $username, string $password, string $role, Workstation $workstation): User;

    /**
     * @param string $value
     * @return Token|null
     * @throws PrestaShopException
     */
    public function findToken(string $value): ?Token;

    /**
     * @param Token $token
     * @return Token
     * @throws PrestaShopException
     */
    public function exchangeToken(Token $token): Token;

    /**
     * @param Token $token
     * @return User
     * @throws PrestaShopException
     */
    public function tokenIntrospection(Token $token): User;

    /**
     * @param Token $token
     * @return Token
     * @throws PrestaShopException
     */
    public function revoke(Token $token): Token;

    /**
     * @param Token $token
     * @param OrderProcess $orderProcess
     * @return Token
     * @throws PrestaShopException
     */
    public function updateTokenProcess(Token $token, OrderProcess $orderProcess): Token;

    /**
     * @param Token $token
     * @return Token
     * @throws PrestaShopException
     */
    public function unsetTokenProcess(Token $token): Token;

}