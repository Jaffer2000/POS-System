<?php

namespace Thirtybees\Module\POS\Api\Response;


use Thirtybees\Module\POS\DependencyInjection\Factory;

class PrintReceiptResponse extends JSendSuccessResponse
{
    /**
     * @var string
     */
    private string $printerId;

    /**
     * @param string $printerId
     */
    public function __construct(string $printerId)
    {
        $this->printerId = $printerId;
    }

    /**
     * @param Factory $factory
     * @return array
     */
    public function getData(Factory $factory): array
    {
        return [
            'printer' => $this->printerId,
            'message' => 'Receipt successfully printed',
        ];
    }


}