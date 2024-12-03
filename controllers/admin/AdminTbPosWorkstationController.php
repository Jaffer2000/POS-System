<?php

use Thirtybees\Module\POS\Workstation\Model\Workstation;
use Thirtybees\Module\POS\Workstation\Service\WorkstationService;

/**
 * Class AdminTbPosWorkstationController.
 */
class AdminTbPosWorkstationController extends ModuleAdminController
{
    /**
     * @var TbPOS
     */
    public $module;

    /**
     * AdminTbPosWorkstationController constructor.
     *
     * @throws PrestaShopException
     * @version 1.0.0 Initial version.
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'tbpos_workstation';
        $this->identifier = 'id_tbpos_workstation';
        parent::__construct();

        $this->_defaultOrderBy = 'a.id_tbpos_workstation';
        $this->_defaultOrderWay = 'ASC';

        $this->actions = ['edit', 'delete'];

        if ($this->hasPrintnodeIntegration()) {
            $this->_join = implode("\n", [
                'LEFT JOIN ' . _DB_PREFIX_ . 'printnode_printer receipt ON (receipt.id_printnode_printer = a.id_printer_receipt)',
                'LEFT JOIN ' . _DB_PREFIX_ . 'printnode_printer regular ON (regular.id_printnode_printer = a.id_printer_regular)'
            ]);
            $this->_select .= ' receipt.name AS printer_receipt_name, regular.name as printer_regular_name';
        } else {
            $this->_select .= ' "-" AS printer_receipt_name, "-" as printer_regular_name';
        }

        $this->fields_list = [
            'id_tbpos_workstation' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name' => [
                'title' => $this->l('Name'),
                'order_key' => 'a.name',
                'filter_key' => 'a!name',
                'type' => 'text',
            ],
            'printer_regular_name' => [
                'title' => $this->l('Printer'),
                'order_key' => 'printer_regular_name',
                'filter_key' => 'printer_regular_name',
                'type' => 'text',
                'tmpTableFilter' => true,
            ],
            'printer_receipt_name' => [
                'title' => $this->l('Receipt printer'),
                'order_key' => 'printer_receipt_name',
                'filter_key' => 'printer_receipt_name',
                'type' => 'text',
                'tmpTableFilter' => true,
            ],
            'active' => [
                'title' => $this->l('Active'),
                'align' => 'text-center',
                'active' => 'active',
                'type' => 'bool',
                'ajax' => true,
                'filter_key' => 'a!active',
            ],
        ];
    }

    /**
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        $workstation = $this->getWorkstation();
        $helper = new HelperForm();
        $this->setHelperDisplay($helper);
        $helper->submit_action = $this->submit_action;
        $helper->show_cancel_button = (isset($this->show_form_cancel_button)) ? $this->show_form_cancel_button : ($this->display == 'add' || $this->display == 'edit');
        $helper->back_url = $this->getBackUrlParameter();
        $helper->fields_value = [
            'id_tbpos_workstation' => $workstation->getId(),
            'name' => $workstation->getName(),
            'active' => $workstation->isActive(),
            'id_printer_receipt' => $workstation->getReceiptPrinterId(),
            'id_printer_regular' => $workstation->getPrinterId(),
        ];
        $hasPrinters = true;
        $printers = $this->getPrinters();
        if (! $this->hasPrintnodeIntegration()) {
            $this->warnings[] = $this->l("Printnode integration is not enabled");
            $helper->fields_value['id_printer_receipt'] = 0;
            $helper->fields_value['id_printer_regular'] = 0;
            $hasPrinters = false;
        }
        return $helper->generateForm([
            [
                'form' => [
                    'legend' => [
                        'title' => $this->l('Workstation'),
                        'icon'  => 'icon-congs',
                    ],
                    'input'  => [
                        [
                            'type'     => 'hidden',
                            'name'     => 'id_tbpos_workstation',
                        ],
                        [
                            'type'     => 'text',
                            'label'    => $this->l('Workstation name'),
                            'name'     => 'name',
                            'required' => true,
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->l('Active'),
                            'name' => 'active',
                            'values' => [
                                [
                                    'id' => 'active_on',
                                    'value' => 1,
                                    'label' => $this->l('Enabled')
                                ],
                                [
                                    'id' => 'active_off',
                                    'value' => 0,
                                    'label' => $this->l('Disabled')
                                ]
                            ]
                        ],
                        [
                            'type'     => 'select',
                            'label'    => $this->l('Receipt printer'),
                            'name'     => 'id_printer_receipt',
                            'options' => [
                                'query' => $printers,
                                'id' => 'id',
                                'name' => 'name'
                            ],
                            'disabled' => !$hasPrinters,
                        ],
                        [
                            'type'     => 'select',
                            'label'    => $this->l('General purpose printer'),
                            'name'     => 'id_printer_regular',
                            'options' => [
                                'query' => $printers,
                                'id' => 'id',
                                'name' => 'name'
                            ],
                            'disabled' => !$hasPrinters,
                        ],
                    ],
                    'submit' => [
                        'title' => $this->l('Save'),
                        'class' => 'btn btn-default pull-right',
                        'name'  => 'submitSave',
                    ],
                ]
            ]
        ]);
    }

    /**
     * @return false|mixed
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitSave')) {
            try {
                $this->display = 'list';
                $workstation = $this->getWorkstation()
                    ->setName((string)Tools::getValue('name'))
                    ->setActive(Tools::getBoolValue('active'));
                if ($this->hasPrintnodeIntegration()) {
                    $workstation
                        ->setPrinterId(Tools::getIntValue('id_printer_regular'))
                        ->setReceiptPrinterId(Tools::getIntValue('id_printer_receipt'));
                }
                return $this->getService()->save($workstation);
            } catch (Throwable $e) {
                $this->display = 'edit';
                $this->errors[] = $e->getMessage();
                return false;
            }
        } else {
            return parent::postProcess();
        }
    }

    /**
     * @return void
     *
     * @throws PrestaShopException
     */
    public function processDelete()
    {
        $workstation = $this->getWorkstation();
        if ($workstation->getId()) {
            $service = $this->getService();
            if ($service->canDelete($workstation)) {
                $service->delete($workstation);
            } else {
                $this->errors[] = $this->l('Cannot delete workstation, there exists records assigned to it');
            }
        }
    }

    /**
     * @return Workstation
     *
     * @throws PrestaShopException
     */
    private function getWorkstation(): Workstation
    {
        $workstationId = Tools::getIntValue('id_tbpos_workstation');
        if ($workstationId) {
            $worstation = $this->getService()->findById($workstationId);
            if ($worstation) {
                return $worstation;
            }
        }
        return Workstation::empty();
    }

    /**
     * @return WorkstationService
     * @throws PrestaShopException
     */
    private function getService(): WorkstationService
    {
        return $this->module->getFactory()->getWorkstationService();
    }

    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    private function hasPrintnodeIntegration(): bool
    {
        return $this->module->getFactory()->getPrintnodeIntegration()->isEnabled();
    }

    /**
     * @return array
     * @throws PrestaShopException
     */
    private function getPrinters(): array
    {
        $printers = [
            0 => [
                'id' => 0,
                'name' => $this->l('-- None --'),
            ]
        ];
        if ($this->hasPrintnodeIntegration()) {
            $prinodeIntegration = $this->module->getFactory()->getPrintnodeIntegration();
            foreach ($prinodeIntegration->getService()->getPrinters() as $printer) {
                $printers[$printer->getId()] = [
                    'id' => $printer->getId(),
                    'name' => $printer->getName()
                ];
            }
        }
        return $printers;
    }

}
