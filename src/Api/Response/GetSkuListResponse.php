<?php

namespace Thirtybees\Module\POS\Api\Response;

use Thirtybees\Module\POS\DependencyInjection\Factory;
use Thirtybees\Module\POS\Sku\Model\Sku;;

/**
 *
 */
class GetSkuListResponse implements Response
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
     */
    public function getResponse(Factory $factory): array
    {
        $resp = [];
        foreach ($this->list as $sku) {
            $resp[] = (new SkuResponse($sku))->getResponse($factory);
        }
        return $resp;
    }

}