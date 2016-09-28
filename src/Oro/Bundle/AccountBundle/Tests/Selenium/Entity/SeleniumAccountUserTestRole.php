<?php

namespace Oro\Bundle\AccountBundle\Tests\Selenium\Entity;

class SeleniumAccountUserTestRole
{
    /** @var null|string */
    public $roleName;

    /** @var array */
    public $permissions = [];

    /**
     * SeleniumAccountUserTestRole constructor.
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
