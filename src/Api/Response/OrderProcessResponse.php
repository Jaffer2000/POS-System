<?php

namespace Thirtybees\Module\POS\Api\Response;

use Cart;
use Order;
use PrestaShopException;
use Thirtybees\Module\POS\DependencyInjection\Factory;
use Thirtybees\Module\POS\Exception\ServerErrorException;
use Thirtybees\Module\POS\OrderProcess\Model\OrderProcess;
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
                $data = $this->addCartData($data, $this->orderProcess->getCart());
                $data = $this->addPaymentMethods($factory, $data);
                return $data;
            case OrderProcess::STATUS_PROCESSING_PAYMENT:
                $data = $this->addCartData($data, $this->orderProcess->getCart());
                $data = $this->addProcessingPaymentData($data);
                return $data;
            case OrderProcess::STATUS_CANCELED:
                return $data;
            case OrderProcess::STATUS_COMPLETED:
                $data = $this->addReceiptData($data, $this->orderProcess->getOrder());
                return $data;
            case OrderProcess::STATUS_PAYMENT_FAILED:
                $data = $this->addCartData($data, $this->orderProcess->getCart());
                $data = $this->addPaymentMethods($factory, $data);
                return $data;
            default:
                throw new ServerErrorException("Invalid order process status: " . $status);
        }
    }

    /**
     * @param array $data
     * @param Cart $cart
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    private function addCartData(array $data, Cart $cart): array
    {
        $subtotal = Tools::roundPrice($cart->getOrderTotal(true, Cart::ONLY_PRODUCTS_WITHOUT_SHIPPING));
        $total = Tools::roundPrice($cart->getOrderTotal(true));
        $totalVatExcl = Tools::roundPrice($cart->getOrderTotal(false));
        $discount = (float)$cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS);
        $lines = [];
        $discounts = [];
        foreach ($cart->getProducts() as $product) {
            $lines[] = [
                'product_id' => (int)$product['id_product'] . '/' . (int)$product['id_product_attribute'],
                'reference' => $product['reference'],
                'name' => $product['name'],
                'item_price' => (float)$product['price'],
                'quantity' => (int)$product['quantity'],
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
        return $data;
    }

    /**
     * @param array $data
     * @param Order $order
     * @return array
     */
    private function addReceiptData(array $data, Order $order): array
    {
        $data['receipt'] = [
          'orderId' => (int)$order->id
        ];
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


}