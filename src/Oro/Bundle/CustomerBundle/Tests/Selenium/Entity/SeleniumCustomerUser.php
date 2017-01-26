<?php

namespace Oro\Bundle\CustomerBundle\Tests\Selenium\Entity;

class SeleniumCustomerUser
{
    /** @var string|null */
    public $email;
    /** @var string|null */
    public $firstName;
    /** @var string|null */
    public $lastName;
    /** @var string|null */
    public $password;
    /** @var string|null */
    public $role;
    /** @var string|null */
    public $customerName;

    /**
     * SeleniumCustomerUser constructor.
     *
     * @param string|null $email
     * @param string|null $firstName
     * @param string|null $lastName
     * @param string|null $password
     * @param string|null $role
     * @param string|null $customerName
     */
    public function __construct(
        $email = null,
        $firstName = null,
        $lastName = null,
        $password = null,
        $role = null,
        $customerName = null
    ) {
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->password = $password;
        $this->role = $role;
        $this->customerName = $customerName;
    }
}
