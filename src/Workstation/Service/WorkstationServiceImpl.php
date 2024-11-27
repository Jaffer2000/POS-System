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

use Db;
use DbQuery;
use PrestaShopException;
use Thirtybees\Module\POS\Exception\NotFoundException;
use Thirtybees\Module\POS\Workstation\Model\Workstation;

class WorkstationServiceImpl implements WorkstationService
{

    /**
     * @param int $id
     *
     * @return Workstation|null
     * @throws PrestaShopException
     */
    public function findById(int $id): ?Workstation
    {
        $conn = Db::getInstance();
        $sql = (new DbQuery())
            ->from('tbpos_workstation')
            ->where('id_tbpos_workstation = ' .(int)$id);
        $row = $conn->getRow($sql);

        if (! $row) {
            return null;
        }
        return $this->toWorkstation($row);
    }

    /**
     * @param int $id
     *
     * @return Workstation
     *
     * @throws NotFoundException
     * @throws PrestaShopException
     */
    public function getById(int $id): Workstation
    {
        $product = $this->findById($id);
        if (! $product) {
            throw new NotFoundException("Workstation with id $id not found");
        }
        return $product;
    }

    /**
     * @return Workstation[]
     *
     * @throws PrestaShopException
     */
    public function findAll(bool $active = true): array
    {
        $conn = Db::getInstance();
        $sql = (new DbQuery())->from('tbpos_workstation');
        if ($active) {
            $sql->where('active = 1');
        }
        $res = $conn->getArray($sql);

        return array_map([$this, 'toWorkstation'], $res);
    }

    /**
     * @param array $row
     * @return Workstation
     */
    private function toWorkstation(array $row): Workstation
    {
        return new Workstation(
            (int)$row['id_tbpos_workstation'],
            (string)$row['name'],
            (bool)$row['active'],
            (int)$row['id_printer_receipt']
        );
    }


}