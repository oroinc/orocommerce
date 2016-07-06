<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Datagrid;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider;

use OroB2B\Bundle\AccountBundle\Acl\Resolver\RoleTranslationPrefixResolver;
use OroB2B\Bundle\AccountBundle\Datagrid\RolePermissionDatasource;

abstract class RolePermissionDatasourceTestCase extends \PHPUnit_Framework_TestCase
{
    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var RolePrivilegeCategoryProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryProvider;

    /** @var AclRoleHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $aclRoleHandler;

    /** @var PermissionManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $permissionManager;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configEntityManager;

    /** @var RoleTranslationPrefixResolver|\PHPUnit_Framework_MockObject_MockObject */
    protected $roleTranslationPrefixResolver;

    /** @var RolePermissionDatasource|\PHPUnit_Framework_MockObject_MockObject */
    protected $datasource;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->permissionManager = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclRoleHandler = $this->getMockBuilder('Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryProvider = $this->getMockBuilder('Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configEntityManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->roleTranslationPrefixResolver = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Acl\Resolver\RoleTranslationPrefixResolver')
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->datasource = $this->getDatasource();
    }

    /**
     * @return RolePermissionDatasource
     */
    abstract protected function getDatasource();

    public function testGetResults()
    {
        $role = new Role();
        $permissionName = 'TEST';
        $privilegeName = 'Account';
        $prefix = 'prefix.key.';

        $identity = 'entity:OroB2B\Bundle\AccountBundle\Entity\Account';
        
        $this->datasource->process($this->getDatagrid($role), []);
        
        $this->aclRoleHandler->expects($this->once())
            ->method('getAllPrivileges')
            ->with($role)
            ->willReturn(
                [
                    'action' => new ArrayCollection(
                        [
                            $this->getAclPrivilege('action:test_action', 'test', new AclPermission('test', 1))
                        ]
                    ),
                    'entity' => new ArrayCollection(
                        [
                            $this->getAclPrivilege(
                                $identity,
                                $privilegeName,
                                new AclPermission($permissionName, AccessLevel::GLOBAL_LEVEL)
                            ),
                            $this->getAclPrivilege(
                                $identity . 'User',
                                'SHARE',
                                new AclPermission('SHARE', AccessLevel::GLOBAL_LEVEL)
                            )
                        ]
                    )
                ]
            );

        $this->categoryProvider->expects($this->once())->method('getPermissionCategories')->willReturn([]);

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($value) {
                    return $value . '_translated';
                }
            );
        
        $this->permissionManager->expects($this->exactly(2))
            ->method('getPermissionByName')
            ->willReturnCallback(
                function ($name) {
                    $permission = new Permission();
                    $permission->setLabel($name . 'Label');

                    return $permission;
                }
            );

        $this->roleTranslationPrefixResolver->expects($this->any())->method('getPrefix')->willReturn($prefix);

        $this->assertResults($this->datasource->getResults(), $identity);

    }

    /**
     * @param array $results
     * @param string $identity
     */
    abstract protected function assertResults(array $results, $identity);

    /**
     * @param string $id
     * @param string $name
     * @param AclPermission $permission
     * @return AclPrivilege|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getAclPrivilege($id, $name, AclPermission $permission)
    {
        $identity = new AclPrivilegeIdentity($id, $name);
        
        /** @var AclPrivilege|\PHPUnit_Framework_MockObject_MockObject $privilege */
        $privilege = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Model\AclPrivilege')
            ->disableOriginalConstructor()
            ->getMock();
        $privilege->expects($this->any())
            ->method('getIdentity')
            ->willReturn($identity);
        $privilege->expects($this->any())
            ->method('getPermissions')
            ->willReturn(
                new ArrayCollection(
                    [
                        $permission->getName() => $permission
                    ]
                )
            );

        return $privilege;
    }

    /**
     * @param Role $role
     * @return DatagridInterface
     */
    protected function getDatagrid(Role $role)
    {
        /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->willReturn(new ParameterBag(['role' => $role]));
        $datagrid->expects($this->once())
            ->method('setDatasource')
            ->with($this->isInstanceOf(RolePermissionDatasource::class));

        return $datagrid;
    }
}
