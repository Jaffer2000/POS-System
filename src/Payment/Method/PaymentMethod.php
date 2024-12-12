<?php

namespace Thirtybees\Module\POS\Payment\Method;

use Thirtybees\Module\POS\OrderProcess\Model\OrderProcess;

interface PaymentMethod
{
    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @param OrderProcess $orderProcess
     * @return array
     */
    public function getActionData(OrderProcess $orderProcess): array;

    /**
     * @return string
     */
    public function getName(): string;
}