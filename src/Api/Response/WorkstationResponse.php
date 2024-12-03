<?php

namespace Thirtybees\Module\POS\Api\Response;

use PrestaShopException;
use Thirtybees\Module\POS\DependencyInjection\Factory;
use Thirtybees\Module\POS\Workstation\Model\Workstation;

/**
 *
 */
class WorkstationResponse extends JSendSuccessResponse
{
    /**
     * @var Workstation
     */
    private Workstation $workstation;

    /**
     * @param Workstation $workstation
     */
    public function __construct(Workstation $workstation)
    {
        $this->workstation = $workstation;
    }

    /**
     * @param Factory $factory
     *
     * @return array
     * @throws PrestaShopException
     */
    public function getData(Factory $factory): array
    {
        return [
            'id' => $this->workstation->getId(),
            'name' => $this->workstation->getName(),
            'receiptPrinter' => $this->getPrinter($factory, $this->workstation->getReceiptPrinterId()),
            'regularPrinter' => $this->getPrinter($factory, $this->workstation->getPrinterId()),
        ];
    }

    /**
     * @param Factory $factory
     * @param int $printerId
     * @return array
     * @throws PrestaShopException
     */
    private function getPrinter(Factory $factory, int $printerId): array
    {
        $printnodeIntegration = $factory->getPrintnodeIntegration();
        if ($printnodeIntegration->isEnabled()) {
            $printers = $printnodeIntegration->getService()->getPrinters();
            if (isset($printers[$printerId])) {
                $printer = $printers[$printerId];
                return [
                    'id' => $printer->getId(),
                    'name' => $printer->getName(),
                ];
            }
        }
        return [
            'id' => 0,
            'name' => 'No printer'
        ];
    }
}