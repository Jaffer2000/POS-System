<?php

namespace Thirtybees\Module\POS\Api\Response;

use Thirtybees\Module\POS\DependencyInjection\Factory;

class InvalidOrderStatusResponse extends JSendFailResponse
{
    /**
     * @var int
     */
    protected int $responseCode;

    /**
     * @var string
     */
    private string $expectedStatus;

    /**
     * @var string
     */
    private string $actualStatus;

    /**
     * @param string $expectedStatus
     * @param string $actualStatus
     * @param int $responseCode
     */
    public function __construct(string $expectedStatus, string $actualStatus, int $responseCode = 400)
    {
        $this->expectedStatus = $expectedStatus;
        $this->actualStatus = $actualStatus;
        $this->responseCode = $responseCode;
    }

    /**
     * @param Factory $factory
     * @return array
     */
    public function getData(Factory $factory): array
    {
        return [
            'code' => 'INVALID_ORDER_STATUS',
            'message' => 'Invalid order status',
            'expectedStatus' => $this->expectedStatus,
            'actualStatus' => $this->actualStatus,
        ];
    }

    /**
     * @return int
     */
    public function getResponseCode(): int
    {
        return $this->responseCode;
    }
}