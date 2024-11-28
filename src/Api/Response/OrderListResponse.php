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
     * @var int
     */
    private int $page;

    /**
     * @var int
     */
    private int $pageSize;

    /**
     * @var int
     */
    private int $totalItems;

    /**
     * @var string
     */
    private string $searchTerm;

    /**
     * @param Order[] $list
     */
    public function __construct(
        array $list,
        int $page,
        int $pageSize,
        int $totalItems,
        string $searchTerm,
    ) {
        $this->searchTerm = $searchTerm;
        $this->list = $list;
        $this->page = $page;
        $this->pageSize = $pageSize;
        $this->totalItems = $totalItems;
    }


    /**
     * @param Factory $factory
     *
     * @return array
     * @throws PrestaShopException
     */
    public function getData(Factory $factory): array
    {
        $list = [];
        foreach ($this->list as $order) {
            $list[] = (new OrderResponse($order))->getData($factory);
        }
        return [
            'list' => $list,
            'pagination' => [
                'searchterm' => $this->searchTerm,
                'current_page' => $this->page,
                'total_items' => $this->totalItems,
                'per_page' => $this->pageSize,
            ]
        ];
    }

}