<?php

namespace Thirtybees\Module\POS\Payment;

use Thirtybees\Module\POS\Exception\NotFoundException;
use Thirtybees\Module\POS\Payment\Method\CashPaymentMethod;
use Thirtybees\Module\POS\Payment\Method\CreditCartPaymentMethod;
use Thirtybees\Module\POS\Payment\Method\PaymentMethod;

class PaymentMethods
{
    /**
     * @return PaymentMethod[]
     */
    public function getMethods(): array
    {
        return [
            CashPaymentMethod::TYPE => new CashPaymentMethod(),
            CreditCartPaymentMethod::TYPE => new CreditCartPaymentMethod(),
        ];
    }

    /**
     * @param string $paymentMethodId
     * @return PaymentMethod
     * @throws NotFoundException
     */
    public function getMethod(string $paymentMethodId): PaymentMethod
    {
        $method = $this->findMethod($paymentMethodId);
        if ($method) {
            return $method;
        }
        throw new NotFoundException("Payment method '$paymentMethodId'not found");
    }

    /**
     * @param string $paymentMethodId
     * @return PaymentMethod
     */
    public function findMethod(string $paymentMethodId): ?PaymentMethod
    {
        $methods = $this->getMethods();
        if (array_key_exists($paymentMethodId, $methods)) {
            return $methods[$paymentMethodId];
        }
        return null;
    }
}