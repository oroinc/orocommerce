<?php

namespace OroB2B\Bundle\UserAdminBundle\Tests\Unit;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

use OroB2B\Bundle\UserAdminBundle\DependencyInjection\OroB2BUserAdminExtension;

class OroB2BUserAdminExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroB2BUserAdminExtension());

        $expectedParameters = [
            "orob2b_user_admin.user.entity.class",
            "orob2b_user_admin.group.entity.class",
            "orob2b_user_admin.form.type.roles.class"
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            "orob2b_user_admin.form.type.roles",
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
