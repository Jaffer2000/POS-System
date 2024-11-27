<?php

namespace Thirtybees\Module\POS\Api\Response;

use Order;
use PrestaShopException;
use Thirtybees\Module\POS\DependencyInjection\Factory;

/**
 *
 */
class OrderListResponse extends JSendSuccessResponse
{
    /**
     * @var Order[]
     */
    private array $list;

    /**
     * @param Order[] $list
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
        foreach ($this->list as $order) {
            $resp[] = (new OrderResponse($order))->getData($factory);
        }
        return $resp;
    }

}