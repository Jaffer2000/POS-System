<?php

namespace Thirtybees\Module\POS\Api\Response;

use Cart;
use PrestaShopException;
use Thirtybees\Module\POS\DependencyInjection\Factory;
use Tools;

/**
 *
 */
class OrderResponse extends JSendSuccessResponse
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
        $subtotal = Tools::roundPrice($this->cart->getOrderTotal(true, Cart::ONLY_PRODUCTS_WITHOUT_SHIPPING));
        $total = Tools::roundPrice($this->cart->getOrderTotal(true));
        $totalVatExcl = Tools::roundPrice($this->cart->getOrderTotal(false));
        $discount = (float)$this->cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
        $lines = [];
        $discounts = [];
        foreach ($this->cart->getProducts() as $product) {
            $lines[] = [
                'product_id' => (int)$product['id_product'] . '/' . (int)$product['id_product_attribute'],
                'reference' => $product['reference'],
                'name' => $product['name'],
                'item_price' => (float)$product['price'],
                'quantity' => (int)$product['quantity'],
            ];
        }
        foreach ($this->cart->getCartRules() as $rule) {
            $discounts[] = [
                'id' => (int)$rule['id_cart_rule'],
                'name' => $rule['name'],
                'type' => (float)$rule['reduction_percent'] > 0 ? 'percentage' : 'amount',
                'value' => (float)$rule['reduction_percent'] > 0 ? (float)$rule['reduction_percent'] : (float)$rule['reduction_amount'],
                'editable' => true
            ];
        }
        return [
            'subtotal' => $subtotal,
            'total' => $total,
            'discount' => $discount,
            'vat' => Tools::roundPrice($total - $totalVatExcl),
            'lines' => $lines,
            'discounts' => $discounts
        ];
    }

}