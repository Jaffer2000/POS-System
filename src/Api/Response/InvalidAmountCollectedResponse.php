<?php

namespace Thirtybees\Module\POS\Api\Response;

use Cart;
use PrestaShopException;
use Thirtybees\Module\POS\DependencyInjection\Factory;
use Tools;

/**
 *
 */
class InvalidAmountCollectedResponse extends JSendFailResponse
{
    /**
     * @var Cart
     */
    private Cart $cart;

    /**
     * @var float
     */
    private float $amount;

    /**
     * @param float $amount
     * @param Cart $cart
     */
    public function __construct(float $amount, Cart $cart)
    {
        $this->amount = $amount;
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
        $expected = $this->cart->getOrderTotal();
        return [
            'action' => 'INVALID_AMOUNT',
            'expectedAmount' => Tools::roundPrice($expected),
            'collectedAmount' => Tools::roundPrice($this->amount),
        ];
    }

    /**
     * @return int
     */
    public function getResponseCode(): int
    {
        return 400;
    }
}