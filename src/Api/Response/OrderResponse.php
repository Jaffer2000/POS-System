<?php

namespace Thirtybees\Module\POS\Api\Response;

use Customer;
use Order;
use PrestaShopException;
use Thirtybees\Module\POS\DependencyInjection\Factory;
use Thirtybees\Module\POS\Utils;

/**
 *
 */
class OrderResponse extends JSendSuccessResponse
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
     *
     * @return array
     * @throws PrestaShopException
     */
    public function getData(Factory $factory): array
    {
        $customer = new Customer($this->order->id_customer);
        $lines = [];
        foreach ($this->order->getProducts() as $product) {
            $productId = (int)$product['id_product'];
            $combinationId = (int)$product['id_product_attribute'];
            $imageUrl = Utils::getProductImageUrl($productId, $combinationId, (string)$product['link_rewrite']);
            $lines[] = [
                'product_id' => $productId . '/' . $combinationId,
                'reference' => $product['product_reference'],
                'name' => $product['product_name'],
                'quantity' => (int)$product['product_quantity'],
                'price_tax_excl' => (float)$product['total_price_tax_excl'],
                'price_tax_incl' => (float)$product['total_price_tax_incl'],
                'image_url' => $imageUrl,
            ];
        }
        return [
            'id' => (int)$this->order->id,
            'reference' => (string)$this->order->reference,
            'date' => $this->order->date_add,
            'customer' => trim($customer->firstname . ' ' . $customer->lastname),
            'total_tax_incl' => (float)$this->order->total_paid_tax_incl,
            'total_tax_excl' => (float)$this->order->total_paid_tax_excl,
            'lines' => $lines,
        ];
    }
}