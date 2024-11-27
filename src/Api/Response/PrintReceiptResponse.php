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
     * @var string
     */
    private string $printJob;

    /**
     * @param string $printerId
     * @param string $printJob
     */
    public function __construct(string $printerId, string $printJob)
    {
        $this->printerId = $printerId;
        $this->printJob = $printJob;
    }

    /**
     * @param Factory $factory
     * @return array
     */
    public function getData(Factory $factory): array
    {
        return [
            'printer' => $this->printerId,
            'printJob' => $this->printJob,
            'message' => 'Receipt successfully printed',
        ];
    }


}