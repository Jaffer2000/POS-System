<?php

namespace Thirtybees\Module\POS\Api\Response;

use PrestaShopException;
use Thirtybees\Module\POS\DependencyInjection\Factory;
use Thirtybees\Module\POS\Sku\Model\Sku;

class MinimalQuantityRequiredResponse extends JSendFailResponse
{
    /**
     * @var int
     */
    private int $minQuantity;

    /**
     * @var Sku
     */
    private Sku $sku;

    /**
     * @param int $minQuantity
     * @param Sku $sku
     */
    public function __construct(Sku $sku, int $minQuantity)
    {
        $this->minQuantity = $minQuantity;
        $this->sku = $sku;
    }

    /**
     * @param Factory $factory
     * @return array
     */
    public function getData(Factory $factory): array
    {
        return [
            'code' => 'MINIMAL_QUANTITY_REQUIRED',
            'skuId' => $this->sku->getSkuId(),
            'minQuantity' => $this->minQuantity,
            'message' => sprintf("Minimal quantity of %s is required", $this->minQuantity),
        ];
    }

    /**
     * @return int
     */
    public function getResponseCode(): int
    {
        return 400;
    }
}