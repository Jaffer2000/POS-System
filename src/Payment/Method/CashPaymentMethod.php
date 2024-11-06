<?php

namespace Thirtybees\Module\POS\Payment\Method;

use PrestaShopException;
use Thirtybees\Module\POS\OrderProcess\Model\OrderProcess;
use Tools;

class CashPaymentMethod implements PaymentMethod
{
    const TYPE = 'CASH';

    /**
     * @return string
     */
    public function getId(): string
    {
        return self::TYPE;
    }

    /**
     * @param OrderProcess $orderProcess
     * @return array
     * @throws PrestaShopException
     */
    public function getActionData(OrderProcess $orderProcess): array
    {
        return [
            'action' => 'COLLECT_CASH',
            'amount' => Tools::roundPrice($orderProcess->getCart()->getOrderTotal()),
        ];
    }
}