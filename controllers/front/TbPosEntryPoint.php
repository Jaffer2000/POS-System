<?php
/**
 * Entry point for TBPOS module
 */

class TbPosEntryPointModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        // Assign CSS and JS files to the template
        $this->context->smarty->assign([
            'tbpos_css' => $this->module->getPathUri() . 'views/css/style.css',
            'tbpos_js' => $this->module->getPathUri() . 'views/js/main.js',
            'tbpos_router' => $this->module->getPathUri() . 'views/js/router.js',
        ]);

        // Display the entry point template
        $this->setTemplate('module:tbpos/views/templates/front/entrypoint.tpl');
    }
}