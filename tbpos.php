<?php

use Thirtybees\Module\POS\Auth\Model\Role;
use Thirtybees\Module\POS\DependencyInjection\Factory;
use Thirtybees\Module\POS\OrderProcess\Model\OrderProcess;

require_once(__DIR__ . '/vendor/autoload.php');

/**
 * Copyright (C) 2022-2022 thirty bees <contact@thirtybees.com>
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Thirty Bees Regular License version 1.0
 * For more information see LICENSE.txt file
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2022-2022 Petr Hucik
 * @license   Licensed under the Thirty Bees Regular License version 1.0
 */

class TbPOS extends PaymentModule
{
    /**
     * @var Factory
     */
    protected $factory;

    public function __construct()
    {
        $this->name = 'tbpos';
        $this->tab = 'back_office_features';
        $this->version = '0.0.1';
        $this->author = 'thirty bees';
        $this->need_instance = false;
        $this->controllers = ['api'];

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('POS');
        $this->description = $this->l('POS');
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];
        $this->tb_versions_compliancy = '>= 1.6.0';
    }

    /**
     * @param bool $createTables
     * @return bool
     * @throws PrestaShopException
     */
    public function install($createTables = true)
    {
        return (
            parent::install() &&
            $this->installDb($createTables) &&
            $this->installTabs() &&
            $this->registerHook('moduleRoutes') &&
            $this->registerHook('actionGetPrintNodeReports') &&
            $this->initSettings()
        );
    }

    /**
     * @param bool $full
     * @return bool
     * @throws PrestaShopException
     */
    public function uninstall($full = true)
    {
        return (
            $this->deleteSettings() &&
            $this->removeTabs() &&
            parent::uninstall() &&
            $this->uninstallDb($full)
        );
    }

    /**
     * @return bool
     * @throws PrestaShopException
     */
    public function reset()
    {
        return (
            $this->uninstall(false) &&
            $this->install(false)
        );
    }

    /**
     * @param bool $create
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function installDb($create)
    {
        if (!$create) {
            return true;
        }
        return $this->executeSqlScript('install');
    }

    /**
     * @param bool $drop
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstallDb($drop)
    {
        if (!$drop) {
            return true;
        }
        return $this->executeSqlScript('uninstall', false);
    }


    /**
     * @return true
     * @throws PrestaShopException
     */
    private function installTabs()
    {
        $wms = $this->installTab(AdminTbPosWorkstationController::class, $this->l('POS system'), 0);
        $this->installTab(AdminTbPosWorkstationController::class, $this->l('Work stations'), $wms);
        return true;
    }

    /**
     * Adds menu item
     *
     * @param string $controllerName
     * @param string $name
     * @param int $parentId
     *
     * @return int
     * @throws PrestaShopException
     */
    private function installTab($controllerName, $name, $parentId)
    {
        $tab = new Tab();
        $tab->module = $this->name;
        $tab->class_name = str_replace("Controller", "", $controllerName);
        $tab->id_parent = $parentId;
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = $name;
        }

        $tab->save();
        return (int)$tab->id;
    }

    /**
     * Removes menu items
     *
     * @return boolean
     * @throws PrestaShopException
     */
    private function removeTabs()
    {
        $tabs = Tab::getCollectionFromModule($this->name);
        foreach ($tabs as $tab) {
            if (! $tab->delete()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param $script
     * @param bool $check
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function executeSqlScript($script, $check = true)
    {
        $file = dirname(__FILE__) . '/sql/' . $script . '.sql';
        if (!file_exists($file)) {
            return false;
        }
        $sql = file_get_contents($file);
        if (!$sql) {
            return true;
        }


        $sql = str_replace(
            [
                'PREFIX_',
                'ENGINE_TYPE',
                'CHARSET_TYPE',
                'COLLATE_TYPE',
                'ENUM_VALUES_ROLES',
                'ENUM_VALUES_ORDER_PROCESS_STATUSES',
            ],
            [
                _DB_PREFIX_,
                _MYSQL_ENGINE_,
                'utf8mb4',
                'utf8mb4_unicode_ci',
                $this->getEnumValues(Role::getRoles()),
                $this->getEnumValues(OrderProcess::validStatuses()),
            ],
            $sql
        );
        $sql = preg_split("/;\s*[\r\n]+/", $sql);
        foreach ($sql as $statement) {
            $stmt = trim($statement);
            if ($stmt) {
                try {
                    if (!Db::getInstance()->execute($stmt)) {
                        PrestaShopLogger::addLog($this->name . ": sql script $script: $stmt: error");
                        if ($check) {
                            return false;
                        }
                    }
                } catch (Exception $e) {
                    PrestaShopLogger::addLog($this->name . ": sql script $script: $stmt: $e");
                    if ($check) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * @return array[]
     */
    public function hookModuleRoutes()
    {
        return [
            'wms' => [
                'controller' => 'api',
                'rule' => 'pos/api/{:apiUrl}',
                'keywords' => [
                    'apiUrl' => ['regexp' => '.*', 'param' => 'apiUrl'],
                ],
                'params' => [
                    'fc' => 'module',
                    'module' => $this->name,
                    'controller' => 'api'
                ]
            ]
        ];
    }

    /**
     * @return Factory
     *
     * @throws PrestaShopException
     */
    public function getFactory(): Factory
    {
        if (is_null($this->factory)) {
            $this->factory = new Factory($this);
        }

        return $this->factory;
    }

    /**
     * @param array $values
     * @return string
     */
    private function getEnumValues(array $values): string
    {
        return "'" . implode("', '", $values) . "'";
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     * @throws SmartyException
     */
    public function getContent()
    {
        $settingsService = $this->getFactory()->getSettingsService();
        $settings = $settingsService->getSettings();

        if (Tools::isSubmit('submitSave')) {
            $settings->setOrderStatusId(Tools::getIntValue('INPUT_ORDER_STATUS_ID'));
            $settings->setTokenExpiration(Tools::getIntValue('INPUT_TOKEN_EXPIRATION'));
            $settings->setDefaultAnonymousCustomerId(Tools::getIntValue('INPUT_DEFAULT_ANONYMOUS_CUSTOMER_ID'));
            $settingsService->saveSettings($settings);

            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true, [
                'configure' => $this->name,
                'conf' => 6
            ]));
        }

        $statuses = [];
        foreach (OrderState::getOrderStates($this->context->language->id) as $status) {
            $statuses[] = [
                'id' => (int)$status['id_order_state'],
                'name' =>  $status['name']
            ];
        }

        $customers = [];
        foreach (Customer::getCustomers(true) as $customer) {
            $customers[] = [
                'id' => (int)$customer['id_customer'],
                'name' => $customer['firstname'] . ' ' . $customer['lastname'] . ' (' . $customer['email'] . ')',
            ];
        }

        $settingsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type'     => 'select',
                        'label'    => $this->l('Paid order status'),
                        'name'     => 'INPUT_ORDER_STATUS_ID',
                        'required' => true,
                        'options' => [
                            'query' => $statuses,
                            'id' => 'id',
                            'name' => 'name'
                        ]
                    ],
                    [
                        'type'     => 'text',
                        'label'    => $this->l('Token expiration (seconds)'),
                        'name'     => 'INPUT_TOKEN_EXPIRATION',
                        'required' => true,
                    ],
                    [
                        'type'     => 'select',
                        'label'    => $this->l('Anonymous customer'),
                        'name'     => 'INPUT_DEFAULT_ANONYMOUS_CUSTOMER_ID',
                        'required' => true,
                        'options' => [
                            'query' => $customers,
                            'id' => 'id',
                            'name' => 'name'
                        ]
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                    'name'  => 'submitSave',
                ],
            ],
        ];

        /** @var AdminController $controller */
        $controller = $this->context->controller;

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = (int)Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSettings';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $controller->getLanguages();
        $helper->fields_value = [
            'INPUT_ORDER_STATUS_ID' => $settings->getOrderStatusId(),
            'INPUT_TOKEN_EXPIRATION' => $settings->getTokenExpiration(),
            'INPUT_DEFAULT_ANONYMOUS_CUSTOMER_ID' => $settings->getDefaultAnonymousCustomerId(),
        ];

        return $helper->generateForm([ $settingsForm ]);
    }

    /**
     * @param array $params
     *
     * @return array
     * @throws PrestaShopException
     */
    public function hookActionGetPrintNodeReports($params): array
    {
        $printnodeIntegratin = $this->getFactory()->getPrintnodeIntegration();
        if ($printnodeIntegratin->isEnabled()) {
            return $printnodeIntegratin->getReports($params['factory']);
        }
        return [];
    }

    /**
     * @return bool
     * @throws PrestaShopException
     */
    private function initSettings()
    {
        $settingsService = $this->getFactory()->getSettingsService();
        $settings = $settingsService->getSettings();
        $settings->setTokenExpiration(3600);
        $settingsService->saveSettings($settings);
        return true;
    }

    /**
     * @return true
     * @throws PrestaShopException
     */
    private function deleteSettings()
    {
        $this->getFactory()->getSettingsService()->deleteSettings();
        return true;
    }

}