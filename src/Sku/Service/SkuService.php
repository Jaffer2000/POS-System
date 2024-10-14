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

namespace Thirtybees\Module\POS\Sku\Service;


use Thirtybees\Module\POS\Exception\NotFoundException;
use Thirtybees\Module\POS\Sku\Model\Sku;;

interface SkuService
{
    /**
     * @param string $reference
     *
     * @return Sku|null
     */
    public function findByReference(string $reference): ?Sku;

    /**
     * @param string $reference
     *
     * @return Sku
     * @throws NotFoundException
     */
    public function getByReference(string $reference): Sku;

    /**
     * @param int $productId
     * @param int $combinationId
     *
     * @return Sku|null
     */
    public function findById(int $productId, int $combinationId): ?Sku;

    /**
     * @param int $productId
     * @param int $combinationId
     *
     * @return Sku
     * @throws NotFoundException
     */
    public function getById(int $productId, int $combinationId): Sku;

    /**
     * @return Sku[]
     */
    public function findAll(): array;


}