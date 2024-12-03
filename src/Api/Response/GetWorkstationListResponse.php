<?php

namespace Thirtybees\Module\POS\Api\Response;

use PrestaShopException;
use Thirtybees\Module\POS\DependencyInjection\Factory;
use Thirtybees\Module\POS\Workstation\Model\Workstation;

/**
 *
 */
class GetWorkstationListResponse extends JSendSuccessResponse
{
    /**
     * @var Workstation[]
     */
    private array $list;

    /**
     * @param Workstation[] $list
     */
    public function __construct(array $list = [])
    {
        $this->list = $list;
    }


    /**
     * @param Factory $factory
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public function getData(Factory $factory): array
    {
        $resp = [];
        foreach ($this->list as $workstation) {
            $workstationResponse = new WorkstationResponse($workstation);
            $resp[] = $workstationResponse->getData($factory);
        }
        return $resp;
    }

}