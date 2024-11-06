<?php

namespace Thirtybees\Module\POS\Api\Response;

abstract class JSendSuccessResponse extends JSendResponse
{
    /**
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_SUCCESS;
    }

    /**
     * @return int
     */
    public function getResponseCode(): int
    {
        return 200;
    }


}