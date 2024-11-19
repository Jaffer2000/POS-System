<?php

namespace Thirtybees\Module\POS\OrderProcess\Service;

use Carrier;
use Cart;
use Configuration;
use Db;
use DbQuery;
use Order;
use PrestaShopException;
use TbPOS;
use Thirtybees\Module\POS\Auth\Model\Token;
use Thirtybees\Module\POS\Auth\Service\AuthService;
use Thirtybees\Module\POS\Exception\ServerErrorException;
use Thirtybees\Module\POS\OrderProcess\Model\OrderProcess;
use Thirtybees\Module\POS\Payment\Method\PaymentMethod;
use Thirtybees\Module\POS\Payment\PaymentMethods;
use Throwable;
use Tools;

class OrderProcessServiceImpl implements OrderProcessService
{
    private TbPOS $module;

    /**
     * @var AuthService
     */
    private AuthService $authService;

    /**
     * @var PaymentMethods
     */
    private PaymentMethods $paymentMethods;

    /**
     * @param TbPOS $module
     * @param AuthService $authService
     * @param PaymentMethods $paymentMethods
     */
    public function __construct(TbPOS $module, AuthService $authService, PaymentMethods $paymentMethods)
    {
        $this->module = $module;
        $this->authService = $authService;
        $this->paymentMethods = $paymentMethods;
    }


    /**
     * @param Token $token
     *
     * @return OrderProcess
     *
     * @throws PrestaShopException
     */
    public function getFromToken(Token $token): OrderProcess
    {
        $orderProcess = $this->findOrderProcess($token->getOrderProcessId());
        if (! $orderProcess) {
            $orderProcess = $this->createOrderProcess($token);
        }
        return $orderProcess;
    }

    /**
     * @param OrderProcess $orderProcess
     * @param string $status
     * @return OrderProcess
     * @throws PrestaShopException
     */
    public function changeStatus(OrderProcess $orderProcess, string $status): OrderProcess
    {
        if (! $orderProcess->canTransitionTo($status)) {
            throw new ServerErrorException("Order can't transition from " . $orderProcess->getStatus() . ' to ' . $status);
        }

        $conn = Db::getInstance();
        $conn->update('tbpos_order_process', [
            'status' => pSQL($status),
        ], 'id_tbpos_order_process = ' . $orderProcess->getId());

        return new OrderProcess(
            $orderProcess->getId(),
            $status,
            $orderProcess->getPaymentMethod(),
            $orderProcess->getCart()
        );
    }

    /**
     * @param OrderProcess $orderProcess
     * @param PaymentMethod $paymentMethod
     * @return OrderProcess
     * @throws PrestaShopException
     */
    public function startPayment(OrderProcess $orderProcess, PaymentMethod $paymentMethod): OrderProcess
    {
        if (! $orderProcess->canTransitionTo(OrderProcess::STATUS_PROCESSING_PAYMENT)) {
            throw new ServerErrorException("Order can't transition to processing payment from status " . $orderProcess->getStatus());
        }

        $conn = Db::getInstance();
        $conn->update('tbpos_order_process', [
            'status' => pSQL(OrderProcess::STATUS_PROCESSING_PAYMENT),
            'payment_method' => pSQL($paymentMethod->getId()),
        ], 'id_tbpos_order_process = ' . $orderProcess->getId());

        return new OrderProcess(
            $orderProcess->getId(),
            OrderProcess::STATUS_PROCESSING_PAYMENT,
            $paymentMethod,
            $orderProcess->getCart()
        );
    }

    /**
     * @param OrderProcess $orderProcess
     * @param PaymentMethod $paymentMethod
     * @param float $amount
     * @param array $paymentMethodData
     *
     * @return OrderProcess
     * @throws PrestaShopException
     */
    public function acceptPayment(
        OrderProcess $orderProcess,
        PaymentMethod $paymentMethod,
        float $amount,
        array $paymentMethodData
    ): OrderProcess
    {
        $order = $this->createOrder($orderProcess->getCart(), $amount, $paymentMethod);
        // TODO: save order id
        return $this->changeStatus($orderProcess, OrderProcess::STATUS_COMPLETED);
    }

    /**
     * @param OrderProcess $orderProcess
     * @return OrderProcess
     * @throws PrestaShopException
     */
    public function cancelPayment(OrderProcess  $orderProcess): OrderProcess
    {
        return $this->changeStatus($orderProcess, OrderProcess::STATUS_ACTIVE);
    }

    /**
     * @param Cart $cart
     * @param float $amount
     * @param PaymentMethod $paymentMethod
     * @return Order
     *
     * @throws PrestaShopException
     */
    private function createOrder(Cart $cart, float $amount, PaymentMethod $paymentMethod): Order
    {
        try {
            if ($this->module->validateOrder(
                $cart->id,
                Configuration::get('PS_OS_BANKWIRE'),
                $amount,
                $this->module->displayName,
                null,
                [],
                (int)$cart->id_currency,
                false,
                $cart->secure_key
            )) {
                return new Order($this->module->currentOrder);
            } else {
                throw new PrestaShopException('Validate order failed');
            }
        } catch (PrestaShopException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new PrestaShopException("Failed to create order", null, $e);
        }
    }

    /**
     * @param Token $token
     * @return OrderProcess
     * @throws PrestaShopException
     */
    public function createOrderProcess(Token $token): OrderProcess
    {
        $carrier = Carrier::getCarrierByReference((int)Configuration::get("TBPOS_CARRIER"));
        $carrierId = $carrier ? (int)$carrier->id : 0;

        $cart = new Cart();
        $cart->id_currency = (int)Configuration::get('PS_CURRENCY_DEFAULT');
        $cart->id_customer = 0;
        $cart->id_carrier = $carrierId;
        $cart->secure_key = md5(Tools::passwdGen(32));
        $cart->save();

        $conn = Db::getInstance();
        $conn->insert('tbpos_order_process', [
            'id_cart' => (int)$cart->id,
            'status' => pSQL(OrderProcess::STATUS_ACTIVE),
            'payment_method' => '',
            'id_tbpos_token' => $token->getId(),
        ]);
        $orderProcessId = (int)$conn->Insert_ID();
        $orderProcess = new OrderProcess(
            $orderProcessId,
            OrderProcess::STATUS_ACTIVE,
            null,
            $cart
        );
        $this->authService->updateTokenProcess($token, $orderProcess);
        return $orderProcess;
    }

    /**
     * @param int $orderProcessId
     * @return OrderProcess|null
     *
     * @throws PrestaShopException
     */
    private function findOrderProcess(int $orderProcessId): ?OrderProcess
    {
        $conn = Db::getInstance();
        $sql = (new DbQuery())
            ->select('op.*')
            ->from('tbpos_order_process', 'op')
            ->where('op.id_tbpos_order_process = ' . $orderProcessId);
        $row = $conn->getRow($sql);

        if ($row) {
            $paymentMethodId = (string)$row['payment_method'];
            $cart = new Cart($row['id_cart']);
            return new OrderProcess(
                $orderProcessId,
                (string)$row['status'],
                $this->paymentMethods->findMethod($paymentMethodId),
                $cart
            );
        }
        return null;
    }
}