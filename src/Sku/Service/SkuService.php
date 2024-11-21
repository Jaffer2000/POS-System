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

    const SEARCH_ALL = 'ALL';
    const SEARCH_BARCODE = 'BARCODE';
    const SEARCH_NAME = 'NAME';
    const SEARCH_REFERENCE = 'REFERENCE';

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
     * @param string $type
     * @param string $search
     *
     * @return Sku[]
     */
    public function find(string $type, string $search): array;


}