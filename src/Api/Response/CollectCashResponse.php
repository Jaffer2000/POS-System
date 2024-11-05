<?php

namespace Thirtybees\Module\POS\Api\Response;

use Cart;
use PrestaShopException;
use Thirtybees\Module\POS\DependencyInjection\Factory;
use Tools;

/**
 *
 */
class CollectCashResponse extends JSendSuccessResponse
{
    /**
     * @var Cart
     */
    private Cart $cart;

    /**
     * @param Cart $cart
     */
    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    /**
     * @param Factory $factory
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public function getData(Factory $factory): array
    {
        $amount = $this->cart->getOrderTotal();
        return [
            'action' => 'COLLECT_CASH',
            'amount' => Tools::roundPrice($amount),
        ];
    }

}