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

use TbPOS;
use Thirtybees\Module\POS\Auth\Service\AuthService;
use Thirtybees\Module\POS\Auth\Service\AuthServiceImpl;
use Thirtybees\Module\POS\OrderProcess\Service\OrderProcessService;
use Thirtybees\Module\POS\OrderProcess\Service\OrderProcessServiceImpl;
use Thirtybees\Module\POS\Payment\PaymentMethods;
use Thirtybees\Module\POS\Sku\Service\SkuService;
use Thirtybees\Module\POS\Sku\Service\SkuServiceImpl;

class Factory
{
    /**
     * @var SkuService
     */
    private SkuService $skuService;

    /**
     * @var AuthService|AuthServiceImpl
     */
    private AuthService  $authService;

    /**
     * @var OrderProcessService
     */
    private OrderProcessService $orderProcessService;

    /**
     * @var PaymentMethods
     */
    private PaymentMethods $paymentMethods;

    /**
     */
    public function __construct(TbPOS $module)
    {
        $this->paymentMethods = new PaymentMethods();
        $this->skuService = new SkuServiceImpl();
        $this->authService = new AuthServiceImpl();
        $this->orderProcessService = new OrderProcessServiceImpl(
            $module,
            $this->authService,
            $this->paymentMethods
        );
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

    /**
     * @return OrderProcessService
     */
    public function getOrderProcessService(): OrderProcessService
    {
        return $this->orderProcessService;
    }

    /**
     * @return PaymentMethods
     */
    public function getPaymentMethods(): PaymentMethods
    {
        return $this->paymentMethods;
    }

}