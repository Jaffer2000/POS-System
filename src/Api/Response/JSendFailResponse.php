<?php

namespace Thirtybees\Module\POS\Api\Response;

abstract class JSendFailResponse extends JSendResponse
{
    /**
     * @return string
     */
    public function getType(): string
    {
        return static::TYPE_FAIL;
    }

}