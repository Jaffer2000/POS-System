<?php

namespace Thirtybees\Module\POS\Api\Response;

use PrestaShopException;
use Thirtybees\Module\POS\DependencyInjection\Factory;

interface Response
{
    /**
     * @param Factory $factory
     *
     * @return array
     * @throws PrestaShopException
     */
    public function getResponse(Factory $factory): array;
}