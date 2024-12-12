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

use PrestaShopException;
use TbPOS;
use Thirtybees\Module\POS\Auth\Service\AuthService;
use Thirtybees\Module\POS\Auth\Service\AuthServiceImpl;
use Thirtybees\Module\POS\Customer\Service\CustomerService;
use Thirtybees\Module\POS\Customer\Service\CustomerServiceImpl;
use Thirtybees\Module\POS\Integration\PrintnodeIntegration;
use Thirtybees\Module\POS\OrderProcess\Service\OrderProcessService;
use Thirtybees\Module\POS\OrderProcess\Service\OrderProcessServiceImpl;
use Thirtybees\Module\POS\Payment\PaymentMethods;
use Thirtybees\Module\POS\Settings\Service\SettingsService;
use Thirtybees\Module\POS\Settings\Service\SettingsServiceConfig;
use Thirtybees\Module\POS\Sku\Service\SkuService;
use Thirtybees\Module\POS\Sku\Service\SkuServiceImpl;
use Thirtybees\Module\POS\Workstation\Service\WorkstationService;
use Thirtybees\Module\POS\Workstation\Service\WorkstationServiceImpl;

class Factory
{
    /**
     * @var SkuService
     */
    private SkuService $skuService;

    /**
     * @var WorkstationService
     */
    private WorkstationService $workstationService;

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
     * @var PrintnodeIntegration
     */
    private PrintnodeIntegration $printNodeIntegration;

    /**
     * @var SettingsService
     */
    private SettingsService $settingsService;

    /**
     * @var CustomerService
     */
    private CustomerService $customerService;

    /**
     * @throws PrestaShopException
     */
    public function __construct(TbPOS $module)
    {
        $this->settingsService = new SettingsServiceConfig();
        $settings = $this->settingsService->getSettings();
        $this->customerService = new CustomerServiceImpl($settings);
        $this->paymentMethods = new PaymentMethods();
        $this->workstationService = new WorkstationServiceImpl();
        $this->skuService = new SkuServiceImpl();
        $this->authService = new AuthServiceImpl();
        $this->orderProcessService = new OrderProcessServiceImpl(
            $module,
            $this->authService,
            $this->paymentMethods,
            $this->customerService,
            $this->workstationService,
            $this->settingsService
        );
        $this->printNodeIntegration = new PrintnodeIntegration($module, $this->orderProcessService);
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

    /**
     * @return WorkstationService
     */
    public function getWorkstationService(): WorkstationService
    {
        return $this->workstationService;
    }

    /**
     * @return PrintnodeIntegration
     */
    public function getPrintnodeIntegration(): PrintnodeIntegration
    {
        return $this->printNodeIntegration;
    }

    /**
     * @return SettingsService
     */
    public function getSettingsService(): SettingsService
    {
        return $this->settingsService;
    }

    /**
     * @return CustomerService
     */
    public function getCustomerService(): CustomerService
    {
        return $this->customerService;
    }

}