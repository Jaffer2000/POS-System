<?php

namespace Thirtybees\Module\POS\Api\Response;

use Order;
use Thirtybees\Module\POS\DependencyInjection\Factory;

/**
 *
 */
class CashPaymentResponse extends JSendSuccessResponse
{
    /**
     * @var Order
     */
    private Order $order;

    /**
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * @param Factory $factory
     * @return array[]
     */
    public function getData(Factory $factory): array
    {
        return [
            'receipt' => [
                'orderId' => (int)$this->order->id
            ]
        ];
    }

}