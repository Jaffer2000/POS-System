<?php

namespace Thirtybees\Module\POS\Api\Response;

use Thirtybees\Module\POS\DependencyInjection\Factory;

class NotFoundResponse extends JSendFailResponse
{
    protected string $reason;

    /**
     * @param string $reason
     */
    public function __construct(string $reason)
    {
        $this->reason = $reason;
    }

    /**
     * @param Factory $factory
     * @return array
     */
    public function getData(Factory $factory): array
    {
        return [
            'code' => 'NOT_FOUND',
            'message' => $this->reason,
        ];
    }

    /**
     * @return int
     */
    public function getResponseCode(): int
    {
        return 404;
    }


}