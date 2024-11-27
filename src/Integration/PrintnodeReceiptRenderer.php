<?php

namespace Thirtybees\Module\POS\Integration;

use HTMLTemplate;
use Order;
use PrestaShopException;
use PrintNodeModule\Model\PageFormat;
use PrintNodeModule\Renderer\HtmlTemplateRenderer;
use RuntimeException;

/**
 * @extends HtmlTemplateRenderer<Order>
 */
class PrintnodeReceiptRenderer extends HtmlTemplateRenderer
{

    /**
     * @var string
     */
    private string $moduleName;

    /**
     * @param string $moduleName
     * @param PageFormat $pageFormat
     * @param string|null $orientation
     */
    public function __construct(string $moduleName, PageFormat $pageFormat, string $orientation = null,)
    {
        parent::__construct($pageFormat, $orientation);
        $this->moduleName = $moduleName;
    }


    /**
     * @param Order $entity
     *
     * @return HTMLTemplate
     * @throws PrestaShopException
     */
    public function getTemplate($entity): HTMLTemplate
    {
        if (!$entity instanceof Order) {
            throw new RuntimeException("Expected Order");
        }
        return new PrintnodeReceiptTemplate($this->moduleName, $entity);
    }

}