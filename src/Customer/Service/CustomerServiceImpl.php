<?php

namespace Thirtybees\Module\POS\Customer\Service;

use Customer as CoreCustomer;
use PrestaShopException;
use Thirtybees\Module\POS\Settings\Model\Settings;
use Thirtybees\Module\POS\Workstation\Model\Workstation;
use Validate;

class CustomerServiceImpl implements CustomerService
{
    /**
     * @var Settings
     */
    private Settings $settings;

    /**
     * @param Settings $settings
     */
    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param Workstation $workstation
     *
     * @return CoreCustomer
     *
     * @throws PrestaShopException
     */
    public function getCustomerForWorkstation(Workstation $workstation): CoreCustomer
    {
        $customerId = $this->settings->getDefaultAnonymousCustomerId();
        return new CoreCustomer($customerId);
    }

    /**
     * @param int $customerId
     * @return CoreCustomer|null
     *
     * @throws PrestaShopException
     */
    public function findCustomer(int $customerId): ?CoreCustomer
    {
        $customer = new CoreCustomer($customerId);
        if (Validate::isLoadedObject($customer)) {
            return $customer;
        }
        return null;
    }


}