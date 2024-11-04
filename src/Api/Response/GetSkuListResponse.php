<?php

namespace Thirtybees\Module\POS\Api\Response;

use PrestaShopException;
use Thirtybees\Module\POS\DependencyInjection\Factory;
use Thirtybees\Module\POS\Sku\Model\Sku;;

/**
 *
 */
class GetSkuListResponse extends JSendSuccessResponse
{
    /**
     * @var Sku[]
     */
    private array $list;

    /**
     * @param Sku[] $list
     */
    public function __construct(array $list = [])
    {
        $this->list = $list;
    }


    /**
     * @param Factory $factory
     *
     * @return array
     * @throws PrestaShopException
     */
    public function getData(Factory $factory): array
    {
        $resp = [];
        foreach ($this->list as $sku) {
            $resp[] = (new SkuResponse($sku))->getData($factory);
        }
        return $resp;
    }

}