<?php

namespace Thirtybees\Module\POS\Api\Response;

use Thirtybees\Module\POS\DependencyInjection\Factory;
use Thirtybees\Module\POS\Sku\Model\Sku;

class OutOfStockResponse extends JSendFailResponse
{
    private Sku $sku;
    private int $quantityAvailable;

    /**
     * @param Sku $sku
     * @param int $quantityAvailable
     */
    public function __construct(Sku $sku, int $quantityAvailable)
    {
        $this->sku = $sku;
        $this->quantityAvailable = $quantityAvailable;
    }


    /**
     * @param Factory $factory
     * @return array
     */
    public function getData(Factory $factory): array
    {
        return [
            'code' => 'OUT_OF_STOCK',
            'skuId' => $this->sku->getSkuId(),
            'quantityAvailable' => $this->quantityAvailable,
            'message' => "Product is out of stock",
        ];
    }

    /**
     * @return int
     */
    public function getResponseCode(): int
    {
        return 422;
    }
}