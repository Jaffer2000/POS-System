<?php

namespace Thirtybees\Module\POS\Settings\Service;

use Thirtybees\Module\POS\Settings\Model\Settings;

interface SettingsService
{
    /**
     * @return Settings
     */
    public function getSettings(): Settings;

    /**
     * @param Settings $settings
     * @return Settings
     */
    public function saveSettings(Settings $settings): Settings;

    /**
     * @return bool
     */
    public function deleteSettings(): bool;
}