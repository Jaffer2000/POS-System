<?php

namespace Thirtybees\Module\POS\Api\Response;

use PrestaShopException;
use Thirtybees\Module\POS\Auth\Model\User;
use Thirtybees\Module\POS\DependencyInjection\Factory;
use Thirtybees\Module\POS\Exception\NotFoundException;

class UserResponse extends JSendSuccessResponse
{

    /**
     * @var User
     */
    private User $user;

    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param Factory $factory
     *
     * @return array
     *
     * @throws PrestaShopException
     * @throws NotFoundException
     */
    public function getData(Factory $factory): array
    {
        $employee = $this->user->getEmployee();
        $token = $this->user->getToken();
        $workstation = $factory->getWorkstationService()->getById($token->getWorkstationId());

        return [
            'username' => (string)$employee->email,
            'role' => $token->getRole(),
            'firstname' => (string)$employee->firstname,
            'lastname' => (string)$employee->lastname,
            'token' => [
                'value' => $token->getValue(),
                'expiresIn' =>  $token->getExpiresIn(),
            ],
            'workstation' => [
                'id' => $workstation->getId(),
                'name' => $workstation->getName(),
            ]
        ];
    }
}