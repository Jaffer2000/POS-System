<?php

namespace Thirtybees\Module\POS\Api\Response;

use Thirtybees\Module\POS\DependencyInjection\Factory;

class BadRequestResponse extends JSendFailResponse
{
    /**
     * @var string
     */
    protected string $reason;

    /**
     * @var int
     */
    protected int $responseCode;

    /**
     * @param string $reason
     * @param int $responseCode
     */
    public function __construct(string $reason, int $responseCode = 400)
    {
        $this->reason = $reason;
        $this->responseCode = $responseCode;
    }

    /**
     * @param Factory $factory
     * @return array
     */
    public function getData(Factory $factory): array
    {
        return [
            'code' => 'BAD_REQUEST',
            'message' => $this->reason,
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