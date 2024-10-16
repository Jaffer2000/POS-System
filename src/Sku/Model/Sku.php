<?php
/**
 * Copyright (C) 2022-2022 thirty bees <contact@thirtybees.com>
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Thirty Bees Regular License version 1.0
 * For more information see LICENSE.txt file
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2022-2022 Petr Hucik
 * @license   Licensed under the Thirty Bees Regular License version 1.0
 */

namespace Thirtybees\Module\POS\Sku\Model;

class Sku
{
    /**
     * @var int
     */
    public $productId;

    /**
     * @var string
     */
    public $productName;

    /**
     * @var int
     */
    public $combinationId;

    /**
     * @var string
     */
    public $combinationName;

    /**
     * @var string
     */
    public $reference;

    /**
     * @var
     */
    public $barcode;

    /**
     * @var string
     */
    public $imageUrl;

    /**
     * @param int $productId
     * @param string $productName
     * @param int $combinationId
     * @param string $combinationName
     * @param string $reference
     * @param string $imageUrl
     */
    public function __construct(
        int $productId,
        string $productName,
        int $combinationId,
        string $combinationName,
        string $reference,
        string $imageUrl
    ) {
        $this->productId = $productId;
        $this->productName = $productName;
        $this->combinationId = $combinationId;
        $this->combinationName = $combinationName;
        $this->reference = $reference;
        $this->imageUrl = $imageUrl;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        if ($this->combinationName) {
            return $this->productName . ': ' . $this->combinationName;
        }
        return $this->productName;
    }

    /**
     * @return string
     */
    public function getSkuId(): string
    {
        return "$this->productId/$this->combinationId";
    }

}