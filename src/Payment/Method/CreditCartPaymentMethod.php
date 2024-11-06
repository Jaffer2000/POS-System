<?php

namespace Thirtybees\Module\POS\Payment\Method;

use Thirtybees\Module\POS\OrderProcess\Model\OrderProcess;

class CreditCartPaymentMethod implements PaymentMethod
{
    const TYPE = 'CREDIT_CARD';

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
     */
    public function getActionData(OrderProcess $orderProcess): array
    {
        return [
            'action' => 'AWAIT_PAYMENT_RESULT'
        ];
    }
}