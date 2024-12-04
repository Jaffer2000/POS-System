<?php

namespace Thirtybees\Module\POS\Settings\Model;

class Settings
{
    /**
     * @var int
     */
    private int $defaultAnonymousCustomerId;

    /**
     * @var int
     */
    private int $orderStatusId;

    /**
     * @var int
     */
    private int $tokenExpiration;

    /**
     * @return int
     */
    public function getDefaultAnonymousCustomerId(): int
    {
        return $this->defaultAnonymousCustomerId;
    }

    /**
     * @return int
     */
    public function getOrderStatusId(): int
    {
        return $this->orderStatusId;
    }

    /**
     * @param int $defaultAnonymousCustomerId
     * @return $this
     */
    public function setDefaultAnonymousCustomerId(int $defaultAnonymousCustomerId): Settings
    {
        $this->defaultAnonymousCustomerId = $defaultAnonymousCustomerId;
        return $this;
    }

    /**
     * @param int $orderStatusId
     * @return $this
     */
    public function setOrderStatusId(int $orderStatusId): Settings
    {
        $this->orderStatusId = $orderStatusId;
        return $this;
    }

    /**
     * @return int
     */
    public function getTokenExpiration(): int
    {
        return $this->tokenExpiration;
    }

    /**
     * @param int $tokenExpiration
     * @return $this
     */
    public function setTokenExpiration(int $tokenExpiration): Settings
    {
        $this->tokenExpiration = $tokenExpiration;
        return $this;
    }

}