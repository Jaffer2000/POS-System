<?php

namespace Thirtybees\Module\POS\Settings\Service;

use Configuration;
use PrestaShopException;
use ReflectionClass;
use Thirtybees\Module\POS\Settings\Model\Settings;

class SettingsServiceConfig implements SettingsService
{
    const SETTINGS_CARRIER_ID = 'TBPOS_CARRIER';
    const SETTINGS_TOKEN_EXPIRATION = 'TBPOS_TOKEN_EXPIRATION';
    const SETTINGS_DEFAULT_ANONYMOUS_CUSTOMER_ID = 'TBPOS_ANONYMOUS_CUSTOMER_ID';

    /**
     * @var Settings
     */
    private Settings $settings;

    /**
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->settings =((new Settings())
            ->setCarrierId($this->getIntValue(static::SETTINGS_CARRIER_ID))
            ->setDefaultAnonymousCustomerId($this->getIntValue(static::SETTINGS_DEFAULT_ANONYMOUS_CUSTOMER_ID))
            ->setTokenExpiration($this->getIntValue(static::SETTINGS_TOKEN_EXPIRATION, 3600))
        );
    }


    /**
     * @return Settings
     */
    public function getSettings(): Settings
    {
        return $this->settings;
    }

    /**
     *
     * @param Settings $settings
     * @return Settings
     *
     * @throws PrestaShopException
     */
    public function saveSettings(Settings $settings): Settings
    {
        $this->settings = $settings;
        $this->setIntValue(static::SETTINGS_CARRIER_ID, $settings->getCarrierId());
        $this->setIntValue(static::SETTINGS_DEFAULT_ANONYMOUS_CUSTOMER_ID, $settings->getDefaultAnonymousCustomerId());
        $this->setIntValue(static::SETTINGS_TOKEN_EXPIRATION, $settings->getTokenExpiration());
        return $settings;
    }

    /**
     * @return bool
     * @throws PrestaShopException
     */
    public function deleteSettings(): bool
    {
        $reflection = new ReflectionClass(static::class);
        foreach ($reflection->getConstants() as $key => $value) {
            if (strpos($key, 'SETTINGS_') === 0) {
                Configuration::deleteByName($key);
            }
        }
        return true;
    }


    /**
     * @param string $settingsKey
     * @param int $defaultValue
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    private function getIntValue(string $settingsKey, int $defaultValue = 0): int
    {
        $value = Configuration::getGlobalValue($settingsKey);
        if (is_null($value) || $value === false) {
            return $defaultValue;
        }
        return (int)$value;
    }

    /**
     * @param string $settingsKey
     * @param int $value
     *
     * @throws PrestaShopException
     */
    private function setIntValue(string $settingsKey, int $value)
    {
        if ($value !== 0) {
            Configuration::updateGlobalValue($settingsKey, $value);
        } else {
            Configuration::deleteByName($settingsKey);
        }
    }
}