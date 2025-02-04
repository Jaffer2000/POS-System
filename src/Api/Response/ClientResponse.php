<?php

namespace Thirtybees\Module\POS\Api\Response;

use Address;
use Context;
use Customer;
use PrestaShopException;
use Thirtybees\Module\POS\DependencyInjection\Factory;

/**
 *
 */
class ClientResponse extends JSendSuccessResponse
{
    /**
     * @var Customer
     */
    private Customer $customer;

    /**
     * @param Customer $customer
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * @param Factory $factory
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public function getData(Factory $factory): array
    {
        $addressses = $this->customer->getAddresses(Context::getContext()->customer->id);
        $addressesData = [];
        foreach ($addressses as $addr) {
            $address = new Address((int)$addr['id_address']);
            $addressesData[] = [
                'id' => (int)$address->id,
                'address1' => $address->address1,
                'address2' => $address->address2,
                'city' => $address->city,
                'postcode' => $address->postcode,
                'country' => (string)$address->country,
                'vatnumber' => $address->vat_number,
                'phone' => $address->phone,
            ];
        }
        return [
            'id' => (int)$this->customer->id,
            'firstname' => $this->customer->firstname,
            'lastname' => $this->customer->lastname,
            'email' => $this->customer->email,
            'addresses' => $addressesData,
        ];
    }
}