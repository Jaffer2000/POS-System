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
    private int $carrierId;

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
    public function getCarrierId(): int
    {
        return $this->carrierId;
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
     * @param int $carrierId
     * @return $this
     */
    public function setCarrierId(int $carrierId): Settings
    {
        $this->carrierId = $carrierId;
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