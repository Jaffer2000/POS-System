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
            $this->registerHook('moduleRoutes')
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
                        PrestaShopLogger::addLog("wms: sql script $script: $stmt: error");
                        if ($check) {
                            return false;
                        }
                    }
                } catch (Exception $e) {
                    PrestaShopLogger::addLog("wms: sql script $script: $stmt: $e");
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
        if (Tools::isSubmit('submitSave')) {
            Configuration::updateGlobalValue('TBPOS_CARRIER', Tools::getIntValue('TBPOS_CARRIER'));
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true, [
                'configure' => $this->name,
                'conf' => 6
            ]));
        }

        $carriers = [];
        foreach (Carrier::getCarriers($this->context->language->id, true) as $carrier) {
            $carriers[] = [
                'id' => (int)$carrier['id_reference'],
                'name' =>  $carrier['name']
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
                        'label'    => $this->l('Carrier'),
                        'name'     => 'TBPOS_CARRIER',
                        'required' => true,
                        'options' => [
                            'query' => $carriers,
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
            'TBPOS_CARRIER' => Configuration::get('TBPOS_CARRIER'),
        ];

        return $helper->generateForm([ $settingsForm ]);
    }

}
