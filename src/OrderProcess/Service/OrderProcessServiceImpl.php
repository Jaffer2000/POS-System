<?php

namespace Thirtybees\Module\POS\OrderProcess\Service;

use Address;
use Cart;
use Configuration;
use Context;
use Db;
use DbQuery;
use Order;
use PrestaShopException;
use TbPOS;
use Thirtybees\Module\POS\Auth\Model\Token;
use Thirtybees\Module\POS\Auth\Service\AuthService;
use Thirtybees\Module\POS\Customer\Service\CustomerService;
use Thirtybees\Module\POS\Exception\NotFoundException;
use Thirtybees\Module\POS\Exception\ServerErrorException;
use Thirtybees\Module\POS\OrderProcess\Model\OrderProcess;
use Thirtybees\Module\POS\Payment\Method\PaymentMethod;
use Thirtybees\Module\POS\Payment\PaymentMethods;
use Thirtybees\Module\POS\Settings\Service\SettingsService;
use Thirtybees\Module\POS\Workstation\Model\Workstation;
use Thirtybees\Module\POS\Workstation\Service\WorkstationService;
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
     * @var CustomerService
     */
    private CustomerService $customerService;

    /**
     * @var WorkstationService
     */
    private WorkstationService $workstationService;

    /**
     * @var SettingsService
     */
    private SettingsService $settingsService;

    /**
     * @param TbPOS $module
     * @param AuthService $authService
     * @param PaymentMethods $paymentMethods
     * @param CustomerService $customerService
     * @param WorkstationService $workstationService
     * @param SettingsService $settingsService
     */
    public function __construct(
        TbPOS              $module,
        AuthService        $authService,
        PaymentMethods     $paymentMethods,
        CustomerService    $customerService,
        WorkstationService $workstationService,
        SettingsService    $settingsService
    )
    {
        $this->module = $module;
        $this->authService = $authService;
        $this->paymentMethods = $paymentMethods;
        $this->customerService = $customerService;
        $this->workstationService = $workstationService;
        $this->settingsService = $settingsService;
    }


    /**
     * @param Token $token
     *
     * @return OrderProcess
     *
     * @throws NotFoundException
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
            $orderProcess->getCart(),
            $orderProcess->getWorkstation(),
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
            $orderProcess->getCart(),
            $orderProcess->getWorkstation(),
        );
    }

    /**
     * @param OrderProcess $orderProcess
     * @param PaymentMethod $paymentMethod
     * @param float $amount
     * @param array $paymentMethodData
     * @param Workstation $workstation
     *
     * @return OrderProcess
     *
     * @throws PrestaShopException
     */
    public function acceptPayment(
        OrderProcess $orderProcess,
        PaymentMethod $paymentMethod,
        float $amount,
        array $paymentMethodData,
    ): OrderProcess
    {
        $order = $this->createOrder($orderProcess->getCart(), $amount, $paymentMethod, $orderProcess->getWorkstation());
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
     * @param Order $order
     * @return OrderProcess|null
     *
     * @throws NotFoundException
     * @throws PrestaShopException
     */
    public function findForOrder(Order $order): ?OrderProcess
    {
        $cartId = (int)$order->id_cart;
        $conn = Db::getInstance();
        $row = $conn->getRow((new DbQuery())
            ->select('*')
            ->from('tbpos_order_process', 'p')
            ->where('id_cart = ' . $cartId)
        );
        if ($row) {
            return $this->returnOrderProcess($row);
        }
        return null;
    }

    /**
     * @param Cart $cart
     * @param float $amount
     * @param PaymentMethod $paymentMethod
     * @param Workstation $workstation
     *
     * @return Order
     *
     * @throws PrestaShopException
     */
    private function createOrder(
        Cart $cart,
        float $amount,
        PaymentMethod $paymentMethod,
        Workstation $workstation
    ): Order {

        try {
            if ($this->module->validateOrder(
                $cart->id,
                $this->settingsService->getSettings()->getOrderStatusId(),
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
     *
     * @return OrderProcess
     *
     * @throws PrestaShopException
     */
    public function createOrderProcess(Token $token): OrderProcess
    {
        $workstation = $this->workstationService->findById($token->getWorkstationId());
        if (! $workstation) {
            throw new ServerErrorException("Workstation not found");
        }
        $customer = $this->customerService->getCustomerForWorkstation($workstation);
        Context::getContext()->customer = $customer;

        $cart = new Cart();
        $cart->id_currency = (int)Configuration::get('PS_CURRENCY_DEFAULT');
        $cart->id_customer = $customer->id;
        $cart->setNoMultishipping();
        $cart->secure_key = md5(Tools::passwdGen(32));
        $cart->id_address_delivery = Address::getFirstCustomerAddressId($customer->id);
        $cart->id_address_invoice = Address::getFirstCustomerAddressId($customer->id);
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
            $cart,
            $workstation
        );
        $this->authService->updateTokenProcess($token, $orderProcess);
        return $orderProcess;
    }

    /**
     * @param int $orderProcessId
     * @return OrderProcess|null
     *
     * @throws NotFoundException
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
            return $this->returnOrderProcess($row);
        }
        return null;
    }

    /**
     * @param array $row
     * @return OrderProcess
     * @throws PrestaShopException
     * @throws NotFoundException
     */
    public function returnOrderProcess(array $row): OrderProcess
    {
        $cart = new Cart((int)$row['id_cart']);
        $tokenId = (int)$row['id_tbpos_token'];
        $token = $this->authService->getTokenById($tokenId);
        $workstation = $this->workstationService->getById($token->getWorkstationId());

        return new OrderProcess(
            (int)$row['id_tbpos_order_process'],
            (string)$row['status'],
            $this->paymentMethods->findMethod((string)$row['payment_method']),
            $cart,
            $workstation
        );
    }
}