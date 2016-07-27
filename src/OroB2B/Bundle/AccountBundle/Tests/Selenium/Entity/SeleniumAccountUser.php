<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Selenium\Entity;

class SeleniumAccountUser
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
    public $accountName;

    /**
     * SeleniumAccountUser constructor.
     *
     * @param string|null $email
     * @param string|null $firstName
     * @param string|null $lastName
     * @param string|null $password
     * @param string|null $role
     * @param string|null $accountName
     */
    public function __construct(
        $email = null,
        $firstName = null,
        $lastName = null,
        $password = null,
        $role = null,
        $accountName = null
    ) {
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->password = $password;
        $this->role = $role;
        $this->accountName = $accountName;
    }
}
