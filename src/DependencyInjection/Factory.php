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

namespace Thirtybees\Module\POS\DependencyInjection;

use Thirtybees\Module\POS\Auth\Service\AuthService;
use Thirtybees\Module\POS\Auth\Service\AuthServiceImpl;
use Thirtybees\Module\POS\Sku\Service\SkuService;
use Thirtybees\Module\POS\Sku\Service\SkuServiceImpl;

class Factory
{
    /**
     * @var SkuService
     */
    private SkuService $skuService;

    private AuthService  $authService;

    /**
     */
    public function __construct()
    {
        $this->skuService = new SkuServiceImpl();
        $this->authService = new AuthServiceImpl();
    }

    /**
     * @return SkuService
     */
    public function getSKUService(): SkuService
    {
        return $this->skuService;
    }

    /**
     * @return AuthService
     */
    public function authService(): AuthService
    {
        return $this->authService;
    }

}