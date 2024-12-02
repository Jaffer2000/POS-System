<?php

namespace Thirtybees\Module\POS\OrderProcess\Service;

use PrestaShopException;
use Thirtybees\Module\POS\Auth\Model\Token;
use Thirtybees\Module\POS\OrderProcess\Model\OrderProcess;
use Thirtybees\Module\POS\Payment\Method\PaymentMethod;
use Thirtybees\Module\POS\Workstation\Model\Workstation;

interface OrderProcessService
{
    /**
     * @param Token $token
     * @return OrderProcess
     * @throws PrestaShopException
     */
    public function getFromToken(Token $token): OrderProcess;

    /**
     * @param Token $token
     *
     * @return OrderProcess
     *
     * @throws PrestaShopException
     */
    public function createOrderProcess(Token $token): OrderProcess;
    /**
     * @param OrderProcess $orderProcess
     * @param string $status
     * @return OrderProcess
     * @throws PrestaShopException
     */
    public function changeStatus(OrderProcess $orderProcess, string $status): OrderProcess;

    /**
     * @param OrderProcess $orderProcess
     * @param PaymentMethod $paymentMethod
     * @return OrderProcess
     * @throws PrestaShopException
     */
    public function startPayment(OrderProcess $orderProcess, PaymentMethod $paymentMethod): OrderProcess;

    /**
     * @param OrderProcess $orderProcess
     * @param PaymentMethod $paymentMethod
     * @param float $amount
     * @param array $paymentMethodData
     * @param Workstation $workstation
     *
     * @return OrderProcess
     * @throws PrestaShopException
     */
    public function acceptPayment(
        OrderProcess $orderProcess,
        PaymentMethod $paymentMethod,
        float $amount,
        array $paymentMethodData,
        Workstation $workstation
    ): OrderProcess;

    /**
     * @param OrderProcess $orderProcess
     *
     * @return OrderProcess
     * @throws PrestaShopException
     */
    public function cancelPayment(OrderProcess $orderProcess);
}