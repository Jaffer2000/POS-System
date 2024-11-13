<?php

namespace Thirtybees\Module\POS\Payment\Method;

use PrestaShopException;
use Thirtybees\Module\POS\OrderProcess\Model\OrderProcess;
use Tools;

class CreditCardOfflinePaymentMethod implements PaymentMethod
{
    const TYPE = 'CREDIT_CARD_OFFLINE';

    /**
     * @return string
     */
    public function getId(): string
    {
        return self::TYPE;
    }

    /**
     * @param OrderProcess $orderProcess
     * @return string[]
     * @throws PrestaShopException
     */
    public function getActionData(OrderProcess $orderProcess): array
    {
        return [
            'action' => 'CAPTURE_CARD_PAYMENT',
            'amount' => Tools::roundPrice($orderProcess->getCart()->getOrderTotal()),
        ];
    }
}