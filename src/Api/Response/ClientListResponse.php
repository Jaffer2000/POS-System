<?php

namespace Thirtybees\Module\POS\Api\Response;

use Customer;
use PrestaShopException;
use Thirtybees\Module\POS\DependencyInjection\Factory;

/**
 *
 */
class ClientListResponse extends JSendSuccessResponse
{
    /**
     * @var Customer[]
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
     * @param Customer[] $list
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
            $list[] = (new ClientResponse($order))->getData($factory);
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