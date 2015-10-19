<?php

namespace OroB2B\src\OroB2B\Bundle\AccountBundle\Tests\Unit\Helper;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Form\Handler\AbstractAccountUserRoleHandler;
use OroB2B\Bundle\AccountBundle\Helper\AccountUserRolePrivilegesHelper;

class AccountUserRolePrivilegesHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|AbstractAccountUserRoleHandler */
    protected $accountUserRoleHandler;

    /**
     * @var AccountUserRolePrivilegesHelper
     */
    protected $accountUserRoleAclPrivilegeHelper;

    protected function setUp()
    {
        $this->accountUserRoleHandler = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserRoleUpdateHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->accountUserRoleAclPrivilegeHelper = new AccountUserRolePrivilegesHelper($this->accountUserRoleHandler);
    }

    public function testCollect()
    {
        $role = new AccountUserRole();
        $privileges = new ArrayCollection();
        $config = [];

        $this->accountUserRoleHandler->expects($this->once())->method('getAccountUserRolePrivileges')
            ->with($role)->willReturn($privileges);
        $this->accountUserRoleHandler->expects($this->once())->method('getAccountUserRolePrivilegeConfig')
            ->with($role)->willReturn($config);

        $result = $this->accountUserRoleAclPrivilegeHelper->collect($role);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame($privileges, $result['data']);
        $this->assertArrayHasKey('privilegesConfig', $result);
        $this->assertSame($config, $result['privilegesConfig']);
        $this->assertArrayHasKey('accessLevelNames', $result);
        $this->assertSame(AccessLevel::$allAccessLevelNames, $result['accessLevelNames']);
    }
}
