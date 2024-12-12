<?php

namespace Thirtybees\Module\POS\Integration;

use Module;
use PrestaShopException;
use PrintNode;
use Employee as CoreEmployee;
use PrintNodeModule\DependencyInjection\Factory as PrintNodeFactory;
use PrintNodeModule\EntityType\OrderEntityType;
use PrintNodeModule\Exception\NotFoundException;
use PrintNodeModule\Model\Report as PrintNodeReport;
use PrintNodeModule\Response\ListResponse;
use PrintNodeModule\Response\PrinterResponse;
use PrintNodeModule\Response\ReportResponse;
use PrintNodeModule\Service\PrintNodeService;
use RuntimeException;
use TbPOS;
use Thirtybees\Module\POS\OrderProcess\Service\OrderProcessService;

class PrintnodeIntegration
{

    /**
     * @var PrintNode|null
     */
    private $printNode;

    /**
     * @var string
     */
    private string $moduleName;

    /**
     * @var OrderProcessService
     */
    private OrderProcessService $orderProcessService;

    /**
     * @throws PrestaShopException
     */
    public function __construct(TbPos $module, OrderProcessService $orderProcessService)
    {
        $this->orderProcessService = $orderProcessService;
        $this->moduleName = (string)$module->name;
        $printNode = Module::getInstanceByName('printnode');
        if ($printNode) {
            $this->printNode = $printNode;
        }
    }

    /**
     * @return PrintNodeService
     */
    public function getService(): PrintNodeService
    {
        if (! $this->printNode) {
            throw new RuntimeException("Printnode integration is not available");
        }
        return $this->printNode->getService();
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return !!$this->printNode;
    }

    /**
     * @return string|null
     * @throws PrestaShopException
     */
    public function getApiUrl(): ?string
    {
        return $this->printNode
            ? $this->printNode->getApiUrl()
            : null;
    }

    /**
     * @param CoreEmployee $employee
     *
     * @return string|null
     * @throws PrestaShopException
     */
    public function getEmployeeToken(CoreEmployee $employee): ?string
    {
        return $this->printNode
            ? $this->printNode->getEmployeeToken($employee)
            : null;
    }

    /**
     * @param PrintNodeFactory $factory
     *
     * @return PrintNodeReport[]
     *
     * @throws NotFoundException
     * @throws PrestaShopException
     */
    public function getReports(PrintNodeFactory $factory): array
    {
        return [
            $this->receiptReport($factory),
        ];
    }

    /**
     * @param PrintNodeFactory $factory
     *
     * @return void
     * @throws PrestaShopException
     * @throws NotFoundException
     */
    private function receiptReport(PrintNodeFactory $factory): PrintNodeReport
    {
        $entityTypeService = $factory->getEntityTypeService();
        $entityType = $entityTypeService->getEntityType(OrderEntityType::TYPE);
        $renderer = new ReceiptRenderer(
            $this->moduleName,
            $this->orderProcessService,
        );
        return new PrintNodeReport($this->moduleName . ':receipt', 'Receipt', $entityType, $renderer);
    }

    /**
     * @param CoreEmployee $employee
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function isEnabledFor(CoreEmployee $employee): bool
    {
        return (
            $this->getEmployeeToken($employee) &&
            $this->getPrintersResponse($employee) &&
            $this->getReportsResponse($employee)
        );
    }

    /**
     * @param CoreEmployee $employee
     *
     * @return int
     */
    public function getEmployeeDefaultPrinter(CoreEmployee $employee): int
    {
        $printNodeFactory = $this->printNode->getFactory();
        $employeeService = $printNodeFactory->getEmployeeService();
        $permissions = $employeeService->getEmployeePermissions($employee);
        $preferences = $employeeService->getEmployeePreferences($employee);
        $printer = $preferences->getDefaultPrinter($permissions);
        if ($printer) {
            return $printer->getId();
        } else {
            return 0;
        }
    }

    /**
     * @param CoreEmployee $employee
     *
     * @return array
     * @throws PrestaShopException
     */
    public function getPrintersResponse(CoreEmployee $employee): array
    {
        $printNodeFactory = $this->printNode->getFactory();
        $permissions = $printNodeFactory->getEmployeeService()->getEmployeePermissions($employee);
        $printers = $printNodeFactory->getPrintersService()->getUsablePrinters($permissions);
        $resp = new ListResponse();
        foreach ($printers as $printer) {
            $resp->add(new PrinterResponse($printer));
        }
        return $resp->getResponse($printNodeFactory);
    }

    /**
     * @param CoreEmployee $employee
     *
     * @return array
     * @throws PrestaShopException
     */
    public function getReportsResponse(CoreEmployee $employee): array
    {
        $printNodeFactory = $this->printNode->getFactory();
        $permissions = $printNodeFactory->getEmployeeService()->getEmployeePermissions($employee);
        $reports = $printNodeFactory->getReportsService()->getUsableReports($permissions);
        $resp = new ListResponse();
        foreach ($reports as $report) {
            $resp->add(new ReportResponse($report, $permissions));
        }
        return $resp->getResponse($printNodeFactory);
    }
}
