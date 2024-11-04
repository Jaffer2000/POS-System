<?php

namespace Thirtybees\Module\POS\Api\Response;

use Thirtybees\Module\POS\Auth\Model\User;
use Thirtybees\Module\POS\DependencyInjection\Factory;

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
     */
    public function getData(Factory $factory): array
    {
        $employee = $this->user->getEmployee();
        $token = $this->user->getToken();

        return [
            'username' => (string)$employee->email,
            'role' => $token->getRole(),
            'firstname' => (string)$employee->firstname,
            'lastname' => (string)$employee->lastname,
            'token' => $token->getValue(),
        ];
    }
}