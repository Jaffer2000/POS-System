<?php

namespace Thirtybees\Module\POS\Customer\Service;

use Thirtybees\Module\POS\Workstation\Model\Workstation;
use Customer as CoreCustomer;

interface CustomerService
{

    /**
     * @param Workstation $workstation
     * @return CoreCustomer
     */
    public function getCustomerForWorkstation(Workstation $workstation): CoreCustomer;

    /**
     * @param int $customerId
     * @return CoreCustomer|null
     */
    public function findCustomer(int $customerId): ?CoreCustomer;

}