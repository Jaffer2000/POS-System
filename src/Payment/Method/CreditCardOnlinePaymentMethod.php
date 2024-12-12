<?php

namespace Thirtybees\Module\POS\Payment\Method;

use Thirtybees\Module\POS\OrderProcess\Model\OrderProcess;

class CreditCardOnlinePaymentMethod implements PaymentMethod
{
    const TYPE = 'CREDIT_CARD_ONLINE';

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

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Credit card';
    }
}