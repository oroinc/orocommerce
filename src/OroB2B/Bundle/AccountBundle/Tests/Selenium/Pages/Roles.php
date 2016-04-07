<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * @method Roles openRoles(string $bundlePath)
 * @method Role add()
 * @method Role open(array $filter)
 * {@inheritdoc}
 */
class Roles extends AbstractPageFilteredGrid
{
    const NEW_ENTITY_BUTTON = "//a[@title='Create Account User Role']";
    const URL = 'admin/account/user/role';

    /**
     * @return Role
     */
    public function entityNew()
    {
        return new Role($this->test);
    }

    /**
     * @return Role
     */
    public function entityView()
    {
        return new Role($this->test);
    }
}
