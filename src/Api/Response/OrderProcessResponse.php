<?php

namespace Thirtybees\Module\POS\Api\Response;

use Cart;
use Customer;
use Order;
use PrestaShopException;
use Product;
use Thirtybees\Module\POS\DependencyInjection\Factory;
use Thirtybees\Module\POS\Exception\ServerErrorException;
use Thirtybees\Module\POS\OrderProcess\Model\OrderProcess;
use Thirtybees\Module\POS\Utils;
use Tools;

/**
 *
 */
class OrderProcessResponse extends JSendSuccessResponse
{
    /**
     * @var OrderProcess
     */
    private OrderProcess $orderProcess;
    /**
     * @param OrderProcess $orderProcess
     */
    public function __construct(OrderProcess $orderProcess)
    {
        $this->orderProcess = $orderProcess;
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
        $status = $this->orderProcess->getStatus();
        $data = [
            'id' => $this->orderProcess->getId(),
            'status' => $status,
        ];

        switch ($status) {
            case OrderProcess::STATUS_ACTIVE:
                $data = $this->addCartData($factory, $data, $this->orderProcess->getCart());
                $data = $this->addPaymentMethods($factory, $data);
                return $data;
            case OrderProcess::STATUS_PROCESSING_PAYMENT:
                $data = $this->addCartData($factory, $data, $this->orderProcess->getCart());
                $data = $this->addProcessingPaymentData($data);
                return $data;
            case OrderProcess::STATUS_CANCELED:
                return $data;
            case OrderProcess::STATUS_COMPLETED:
                $data = $this->addOrderData($factory, $data, $this->orderProcess->getOrder());
                return $data;
            case OrderProcess::STATUS_PAYMENT_FAILED:
                $data = $this->addCartData($factory, $data, $this->orderProcess->getCart());
                $data = $this->addPaymentMethods($factory, $data);
                return $data;
            default:
                throw new ServerErrorException("Invalid order process status: " . $status);
        }
    }

    /**
     * @param Factory $factory
     * @param array $data
     * @param Cart $cart
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    private function addCartData(Factory $factory, array $data, Cart $cart): array
    {
        $subtotal = Tools::roundPrice($cart->getOrderTotal(true, Cart::ONLY_PRODUCTS_WITHOUT_SHIPPING));
        $total = Tools::roundPrice($cart->getOrderTotal(true));
        $totalVatExcl = Tools::roundPrice($cart->getOrderTotal(false));
        $discount = (float)$cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
        $lines = [];
        $discounts = [];
        foreach ($cart->getProducts() as $product) {
            $productId = (int)$product['id_product'];
            $combinationId = (int)$product['id_product_attribute'];
            $imageUrl = Utils::getProductImageUrl($productId, $combinationId, (string)$product['link_rewrite']);
            $lines[] = [
                'product_id' => $productId . '/' . $combinationId,
                'reference' => $product['reference'],
                'name' => $product['name'],
                'quantity' => (int)$product['quantity'],
                'price_tax_excl' => (float)Product::getPriceStatic($productId, false, $combinationId),
                'price_tax_incl' => (float)Product::getPriceStatic($productId, true, $combinationId),
                'image_url' => $imageUrl,
            ];
        }
        foreach ($cart->getCartRules() as $rule) {
            $discounts[] = [
                'id' => (int)$rule['id_cart_rule'],
                'name' => $rule['name'],
                'type' => (float)$rule['reduction_percent'] > 0 ? 'percentage' : 'amount',
                'value' => (float)$rule['reduction_percent'] > 0 ? (float)$rule['reduction_percent'] : (float)$rule['reduction_amount'],
                'editable' => true
            ];
        }

        $data['cart'] = [
            'subtotal' => $subtotal,
            'total' => $total,
            'discount' => $discount,
            'vat' => Tools::roundPrice($total - $totalVatExcl),
            'lines' => $lines,
            'discounts' => $discounts
        ];

        return $this->addClientData($factory, $cart, $data);
    }

    /**
     * @param Factory $factory
     * @param array $data
     * @param Order $order
     * @return array
     * @throws PrestaShopException
     */
    private function addOrderData(Factory $factory, array $data, Order $order): array
    {
        $orderResponse = new OrderResponse($order);
        $data['order'] = $orderResponse->getData($factory);
        return $data;
    }

    /**
     * @param array $data
     * @return array
     */
    private function addProcessingPaymentData(array $data): array
    {
        $method = $this->orderProcess->getPaymentMethod();
        $paymentInfo = [
            'method' => $method->getId()
        ];
        $paymentInfo = array_merge($paymentInfo, $method->getActionData($this->orderProcess));
        $data['payment'] = $paymentInfo;
        return $data;
    }

    /**
     * @param Factory $factory
     * @param array $data
     * @return array
     */
    private function addPaymentMethods(Factory $factory, array $data)
    {
        $methods = $factory->getPaymentMethods()->getMethodsAvailableForOrderProcess($this->orderProcess);
        $paymentMethods = [];
        foreacH ($methods as $method) {
            $paymentMethods[] = $method->getId();
        }
        $data['availablePaymentMethods'] = $paymentMethods;
        return $data;
    }

    /**
     * @param Factory $factory
     * @param Cart $cart
     * @param array $data
     * @return array
     * @throws PrestaShopException
     */
    private function addClientData(Factory $factory, Cart $cart, array $data)
    {
        $clientResponse = new ClientResponse(new Customer($cart->id_customer));
        $data['client'] = $clientResponse->getData($factory);
        return $data;
    }

}