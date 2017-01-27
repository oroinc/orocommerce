<?php

namespace Oro\Bundle\CustomerBundle\Tests\Selenium\Entity;

class SeleniumCustomerUserTestRole
{
    /** @var null|string */
    public $roleName;

    /** @var array */
    public $permissions = [];

    /**
     * SeleniumCustomerUserTestRole constructor.
     *
     * @param string $roleName
     * @param array $permissions
     */
    public function __construct($roleName = null, array $permissions = [])
    {
        $this->roleName = $roleName;
        $this->permissions = $permissions;
    }
}
