<?php

namespace Thirtybees\Module\POS\Api\Response;

use Thirtybees\Module\POS\DependencyInjection\Factory;
use Thirtybees\Module\POS\Sku\Model\Sku;;

/**
 *
 */
class SkuResponse implements Response
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
     */
    public function getResponse(Factory $factory): array
    {
        return [
            'name' => $this->sku->getName(),
            'refcode' => $this->sku->reference,
            'barcode' => $this->sku->barcode,
            'product_id' => $this->sku->getSkuId(),
            'price' => $this->sku->price,
            'image_url' => $this->sku->imageUrl,
        ];
    }

}