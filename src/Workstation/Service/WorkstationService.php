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

namespace Thirtybees\Module\POS\Workstation\Service;



use PrestaShopException;
use Thirtybees\Module\POS\Exception\NotFoundException;
use Thirtybees\Module\POS\Workstation\Model\Workstation;

;

interface WorkstationService
{
    /**
     * @param int $id
     *
     * @return Workstation|null
     *
     * @throws PrestaShopException
     */
    public function findById(int $id): ?Workstation;

    /**
     * @param int $id
     *
     * @return Workstation
     *
     * @throws PrestaShopException
     * @throws NotFoundException
     */
    public function getById(int $id): Workstation;

    /**
     * @return Workstation[]
     *
     * @throws PrestaShopException
     */
    public function findAll(bool $active = true): array;


}