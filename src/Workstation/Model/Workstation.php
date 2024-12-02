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

namespace Thirtybees\Module\POS\Workstation\Model;

class Workstation
{
    /**
     * @var int
     */
    private int $id;

    /**
     * @var string
     */
    private string $name;


    /**
     * @var bool
     */
    private bool $active;

    /**
     * @var int
     */
    private int $receiptPrinterId;

    /**
     * @var int
     */
    private int $printerId;

    /**
     * @param int $id
     * @param string $name
     * @param bool|int $active
     * @param int $receiptPrinterId
     * @param int $regularPrinterId
     */
    public function __construct(int $id, string $name, bool $active, int $receiptPrinterId, int $regularPrinterId)
    {
        $this->id = $id;
        $this->name = $name;
        $this->active = $active;
        $this->receiptPrinterId = $receiptPrinterId;
        $this->printerId = $regularPrinterId;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @return int
     */
    public function getReceiptPrinterId(): int
    {
        return $this->receiptPrinterId;
    }

    /**
     * @return int
     */
    public function getPrinterId(): int
    {
        return $this->printerId;
    }


}