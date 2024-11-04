<?php

namespace Thirtybees\Module\POS\Api\Response;

use PrestaShopException;
use Product;
use Thirtybees\Module\POS\DependencyInjection\Factory;
use Thirtybees\Module\POS\Sku\Model\Sku;;

/**
 *
 */
class SkuResponse extends JSendSuccessResponse
{
    /**
     * @var Sku
     */
    private Sku $sku;

    /**
     * @param Sku $sku
     */
    public function __construct(Sku $sku)
    {
        $this->sku = $sku;
    }

    /**
     * @param Factory $factory
     *
     * @return array
     * @throws PrestaShopException
     */
    public function getData(Factory $factory): array
    {
        return [
            'name' => $this->sku->getName(),
            'refcode' => $this->sku->reference,
            'barcode' => $this->sku->barcode,
            'product_id' => $this->sku->getSkuId(),
            'price_tax_excl' => Product::getPriceStatic($this->sku->productId, false, $this->sku->combinationId),
            'price_tax_incl' => Product::getPriceStatic($this->sku->productId, true, $this->sku->combinationId),
            'image_url' => $this->sku->imageUrl,
        ];
    }

}