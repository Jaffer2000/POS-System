<?php

namespace Thirtybees\Module\POS\Api\Response;

use Thirtybees\Module\POS\DependencyInjection\Factory;
use Tools;

/**
 *
 */
class InvalidAmountCollectedResponse extends JSendFailResponse
{
    /**
     * @var float
     */
    private float $exptected;

    /**
     * @var float
     */
    private float $amount;

    /**
     * @param float $amount
     * @param float $exptected
     */
    public function __construct(float $amount, float $exptected)
    {
        $this->amount = $amount;
        $this->exptected = $exptected;
    }

    /**
     * @param Factory $factory
     *
     * @return array
     */
    public function getData(Factory $factory): array
    {
        return [
            'action' => 'INVALID_AMOUNT',
            'expectedAmount' => Tools::roundPrice($this->exptected),
            'collectedAmount' => Tools::roundPrice($this->amount),
        ];
    }

    /**
     * @return int
     */
    public function getResponseCode(): int
    {
        return 400;
    }
}