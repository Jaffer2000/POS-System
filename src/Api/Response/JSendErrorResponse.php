<?php

namespace Thirtybees\Module\POS\Api\Response;

use Thirtybees\Module\POS\DependencyInjection\Factory;

class JSendErrorResponse extends JSendResponse
{
    /**
     * @var string
     */
    protected string $message;

    /**
     * @param string $message
     */
    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * @param Factory $factory
     * @return string
     */
    public function getMessage(Factory $factory): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return static::TYPE_ERROR;
    }

    /**
     * @return int
     */
    public function getResponseCode(): int
    {
        return 500;
    }


}