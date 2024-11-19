<?php


namespace Thirtybees\Module\POS\OrderProcess\Model;

use Cart;
use Order;
use PrestaShopException;
use Thirtybees\Module\POS\Exception\ServerErrorException;
use Thirtybees\Module\POS\Payment\Method\PaymentMethod;
use Validate;

class OrderProcess
{
    const STATUS_ACTIVE = "ACTIVE";
    const STATUS_CANCELED = "CANCELED";
    const STATUS_PROCESSING_PAYMENT = "PROCESSING_PAYMENT";
    const STATUS_COMPLETED = "COMPLETED";
    const STATUS_PAYMENT_FAILED = "PAYMENT_FAILED";

    /**
     * @var int
     */
    private int $id;

    /**
     * @var Cart
     */
    private Cart $cart;

    /**
     * @var string
     */
    private string $status;

    /**
     * @var PaymentMethod|null
     */
    private ?PaymentMethod $paymentMethod;

    /**
     * @return PaymentMethod|null
     */
    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }

    /**
     * @param int $id
     * @param string $status
     * @param PaymentMethod|null $paymentMethod
     * @param Cart $cart
     */
    public function __construct(
        int $id,
        string $status,
        ?PaymentMethod $paymentMethod,
        Cart $cart
    ) {
        $this->id = (int)$id;
        $this->status = static::validStatus($status);
        $this->paymentMethod = $paymentMethod;
        $this->cart = $cart;
    }

    /**
     * @return string[]
     */
    public static function validStatuses(): array
    {
        return array_keys(static::getTransitionMap());
    }

    /**
     * @return array
     */
    private static function getTransitionMap(): array
    {
        return [
            static::STATUS_ACTIVE => [
                static::STATUS_PROCESSING_PAYMENT,
                static::STATUS_CANCELED,
            ],
            static::STATUS_PROCESSING_PAYMENT => [
                static::STATUS_ACTIVE,
                static::STATUS_COMPLETED,
                static::STATUS_PAYMENT_FAILED,
                static::STATUS_CANCELED,
            ],
            static::STATUS_PAYMENT_FAILED => [
                static::STATUS_ACTIVE,
                static::STATUS_CANCELED,
            ],
            static::STATUS_CANCELED => [],
            static::STATUS_COMPLETED => [],
        ];
    }

    /**
     * @return Cart
     */
    public function getCart(): Cart
    {
        return $this->cart;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }


    /**
     * @param string $status
     * @return string
     */
    private static function validStatus(string $status): string
    {
        if (in_array($status, static::validStatuses())) {
            return $status;
        }
        throw new ServerErrorException("Invalid status: " . $status);
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return bool
     */
    public function canBeModified(): bool
    {
        return $this->status === static::STATUS_ACTIVE;
    }

    /**
     * @param string $newStatus
     * @return bool
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $newStatus = static::validStatus($newStatus);
        $map = static::getTransitionMap();
        $allowedStatuses = $map[$this->getStatus()];
        return in_array($newStatus, $allowedStatuses);
    }

    /**
     * @return Order
     *
     * @throws PrestaShopException
     */
    public function getOrder(): Order
    {
        if ($this->status === static::STATUS_COMPLETED) {
            $orderId = (int)Order::getOrderByCartId($this->cart->id);
            $order = new Order($orderId);
            if (!Validate::isLoadedObject($order)) {
                throw new ServerErrorException("Order not found: " . $orderId);
            }
            return $order;
        }
        throw new ServerErrorException("Invalid order status: " . $this->status);
    }
}