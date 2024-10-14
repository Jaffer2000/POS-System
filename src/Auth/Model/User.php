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

namespace Thirtybees\Module\POS\Auth\Model;

use Employee as CoreEmployee;

class User
{
    /**
     * @var CoreEmployee
     */
    private CoreEmployee $employee;

    /**
     * @var Token
     */
    private Token $token;


    /**
     * @param CoreEmployee $employee
     * @param Token $token
     */
    public function __construct(CoreEmployee $employee, Token $token)
    {
        $this->employee = $employee;
        $this->token = $token;
    }

    /**
     * @return CoreEmployee
     */
    public function getEmployee(): CoreEmployee
    {
        return $this->employee;
    }

    /**
     * @return Token
     */
    public function getToken(): Token
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->token->getRole();
    }



}