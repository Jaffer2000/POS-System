<?php

namespace Thirtybees\Module\POS\Integration;

use Order;
use PrestaShopException;
use PrintNodeModule\Renderer\EscPosRawRenderer;
use Thirtybees\Module\POS\Exception\InvalidArgumentException;
use Thirtybees\Module\POS\Exception\NotFoundException;
use Thirtybees\Module\POS\OrderProcess\Model\OrderProcess;
use Thirtybees\Module\POS\OrderProcess\Service\OrderProcessService;

class ReceiptRenderer extends EscPosRawRenderer
{

    /**
     * @var OrderProcessService
     */
    private OrderProcessService $orderProcessService;

    /**
     * @param string $moduleName
     * @param OrderProcessService $orderProcessService
     *
     * @throws PrestaShopException
     */
    public function __construct(
        string $moduleName,
        OrderProcessService $orderProcessService
    ) {
        parent::__construct(_PS_MODULE_DIR_ . $moduleName . '/print/esc-pos/receipt.tpl');
        $this->orderProcessService = $orderProcessService;
    }

    /**
     * @param Order $entity
     *
     * @return array
     *
     * @throws NotFoundException
     * @throws PrestaShopException
     */
    public function getTemplateParameters($entity): array
    {
        if (! $entity instanceof Order) {
            throw new InvalidArgumentException('Expected order');
        }
        $order = $entity;

        $orderProcess = $this->orderProcessService->findForOrder($order);
        if (! $orderProcess) {
            throw new NotFoundException("Order process not found for order " . $order->id);
        }
        if ($orderProcess->getStatus() !== OrderProcess::STATUS_COMPLETED) {
            throw new InvalidArgumentException('Order process is not completed yet');
        }

        $paymentMethod = $orderProcess->getPaymentMethod();
        $workstation = $orderProcess->getWorkstation();

        // provide data to receipt.tpl template
        return array_merge([
            'order' => $order,
            'taxBreakdown' => $order->getProductTaxesBreakdown(),
            'paymentMethod' => [
                'id' => $paymentMethod->getId(),
                'name' => $paymentMethod->getName(),
            ],
            'workstation' => [
                'id' => $workstation->getId(),
                'name' => $workstation->getName(),
            ],
        ], parent::getTemplateParameters($entity)) ;
    }



}