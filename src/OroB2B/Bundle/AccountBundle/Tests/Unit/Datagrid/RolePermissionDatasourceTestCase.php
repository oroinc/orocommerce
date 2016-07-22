<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Datagrid;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
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

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($value) {
                    return $value . '_translated';
                }
            );

        $this->permissionManager = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->permissionManager->expects($this->any())
            ->method('getPermissionByName')
            ->willReturnCallback(
                function ($name) {
                    $permission = new Permission();
                    $permission->setLabel($name . 'Label');

                    return $permission;
                }
            );

        $this->aclRoleHandler = $this->getMockBuilder('Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryProvider = $this->getMockBuilder('Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryProvider->expects($this->any())->method('getPermissionCategories')->willReturn([]);

        $this->configEntityManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->roleTranslationPrefixResolver = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Acl\Resolver\RoleTranslationPrefixResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $this->roleTranslationPrefixResolver->expects($this->any())->method('getPrefix')->willReturn('prefix.key.');
    }

    /**
     * @param RolePermissionDatasource $datasource
     * @param string $identity
     * @return array|ResultRecordInterface[]
     */
    protected function retrieveResultsFromPermissionsDatasource(RolePermissionDatasource $datasource, $identity)
    {
        $role = new Role();
        
        $datasource->process($this->getDatagrid($role), []);
        
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
                                'TEST',
                                new AclPermission('TEST', AccessLevel::GLOBAL_LEVEL)
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
        
        return $datasource->getResults();
    }

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
