<?php
namespace Thirtybees\Module\POS\Integration;

use Context;
use HTMLTemplate;
use PrestaShopException;
use Order;
use Shop;
use SmartyException;
use Tools;

class PrintnodeReceiptTemplate extends HTMLTemplate
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var string
     */
    protected $qrCode;

    /**
     * @var string
     */
    private string $moduleName;

    /**
     * @param string $moduleName
     * @param Order $order
     *
     * @throws PrestaShopException
     */
    public function __construct(string $moduleName, Order $order)
    {
        $this->moduleName = $moduleName;
        $this->smarty = Context::getContext()->smarty;
        $this->order = $order;
        $this->title = 'Receipt';
        $this->date = Tools::displayDate($order->date_add);
        $this->shop = new Shop((int) $order->id_shop);
    }

    /**
     * @return string
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function getContent()
    {
        $this->smarty->assign([
            'order' => $this->order,
        ]);
        $smartyTemplate = $this->getTemplate('receipt');
        if (! $smartyTemplate) {
            throw new PrestaShopException("Template 'receipt' not found");
        }
        return $this->smarty->fetch($smartyTemplate);
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return 'receipt_' . (int)$this->order->id . '.pdf';
    }

    /**
     * @return string
     */
    public function getBulkFilename()
    {
        return 'receipts.tpl';
    }

    /**
     * @param string $templateName
     *
     * @return string
     * @throws PrestaShopException
     */
    protected function getTemplate($templateName)
    {
        $system = parent::getTemplate($templateName);
        if ($system) {
            return $system;
        }
        // use local version
        $path = _PS_MODULE_DIR_ . '/'.$this->moduleName.'/pdf/' . $templateName . '.tpl';
        if (file_exists($path)) {
            return $path;
        }
        throw new PrestaShopException("Template '$templateName' not found");
    }

    /**
     * @return string
     */
    public function getHeader()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getFooter()
    {
        return '';
    }

    /**
     * @return string
     */
    protected function getLogo()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getPagination()
    {
        return '';
    }

}

