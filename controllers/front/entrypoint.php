<?php
/**
 * Entry point for TBPOS module
 */

class TbPosEntryPointModuleFrontController extends ModuleFrontController
{
    /**
     * @var TbPOS
     */
    public $module;

    /**
     * @return void
     *
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function initContent()
    {
        $template = $this->context->smarty->createTemplate($this->getTemplatePath('entrypoint.tpl'));
        // Assign CSS and JS files to the template
        $link = $this->context->link;

        $template->assign([
            'apiUrl' => rtrim($link->getModuleLink('tbpos', 'api', ['apiUrl' => '']), '/'),
            'jsFiles' => [
                $this->module->getPathUri() . 'views/js/app/app.js',
                $this->module->getPathUri() . 'views/js/app/chunk-vendors.js',
                $this->module->getPathUri() . 'views/js/bootstrap.bundle.min.js',
            ],
            'cssFiles' => [
                $this->module->getPathUri() . 'views/css/bootstrap.min.css',
                $this->module->getPathUri() . 'views/css/fontawesome.min.css',
                $this->module->getPathUri() . 'views/css/style.css',
            ],
            'translations' => $this->getTranslations(),
        ]);        

        die($template->fetch());
    }

    /**
     * @return array
     */
    public function getTranslations(): array
    {
        return [
            'outOfStock' => $this->l('Product is out of stock.'),
            'barcodeNotFound' => $this->l("Barcode not found for this barcode."),
            'invalidDiscount' =>  $this->l('Invalid discount amount entered. Please check your input and try again.'),
            'paymentWarning' =>  $this->l('Please select a payment method.'),
            'paymentConfirmation' => $this->l('Was the payment processed successfully?'),
            'receiptConfirmation' => $this->l('Would you like a receipt?'),
            'loginFailed' => $this->l('Login failed. Please check your login credentials.'),
        ];
    }

    /**
     * @param string $str
     * @return string
     */
    public function l(string $str): string
    {
        return Translate::getModuleTranslation($this->module->name, $str, 'entrypoint');
    }
}