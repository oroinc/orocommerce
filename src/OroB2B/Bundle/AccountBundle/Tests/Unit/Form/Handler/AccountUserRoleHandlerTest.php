<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Handler;

use Symfony\Component\Form\FormFactory;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclPrivilegeRepository;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserRoleType;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserRoleHandler;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository;
use OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

class AccountUserRoleHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormFactory
     */
    protected $formFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AclManager
     */
    protected $aclManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AclPrivilegeRepository
     */
    protected $privilegeRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ChainMetadataProvider
     */
    protected $chainMetadataProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigProviderInterface
     */
    protected $ownershipConfigProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AccountUserRoleRepository
     */
    protected $roleRepository;

    /**
     * @var AccountUserRoleHandler
     */
    protected $handler;

    /**
     * @var array
     */
    protected $privilegeConfig = [
        'entity' => ['types' => ['entity'], 'fix_values' => false, 'show_default' => true],
        'action' => ['types' => ['action'], 'fix_values' => false, 'show_default' => true],
    ];

    /**
     * @var array
     */
    protected $permissionNames = [
        'entity' => ['entity_name'],
        'action' => ['action_name'],
    ];

    protected function setUp()
    {
        $this->formFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclManager = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->privilegeRepository =
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Persistence\AclPrivilegeRepository')
                ->disableOriginalConstructor()
                ->getMock();

        $this->chainMetadataProvider =
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider')
                ->disableOriginalConstructor()
                ->getMock();

        $this->ownershipConfigProvider
            = $this->getMock('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface');

        $this->roleRepository =
            $this->getMockBuilder('\OroB2B\Bundle\AccountBundle\Entity\Repository\AccountUserRoleRepository')
                ->disableOriginalConstructor()
                ->getMock();

        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->doctrineHelper = $this->getMockBuilder('\Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->setConstructorArgs([$this->managerRegistry])
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($this->roleRepository);

        $this->handler = new AccountUserRoleHandler($this->formFactory, $this->privilegeConfig);
        $this->handler->setAclManager($this->aclManager);
        $this->handler->setAclPrivilegeRepository($this->privilegeRepository);
        $this->handler->setChainMetadataProvider($this->chainMetadataProvider);
        $this->handler->setOwnershipConfigProvider($this->ownershipConfigProvider);
        $this->handler->setManagerRegistry($this->managerRegistry);
        $this->handler->setDoctrineHelper($this->doctrineHelper);
    }

    public function testCreateForm()
    {
        $role = new AccountUserRole('TEST');

        $expectedConfig = $this->privilegeConfig;
        foreach ($expectedConfig as $key => $value) {
            $expectedConfig[$key]['permissions'] = $this->getPermissionNames($value['types']);
        }

        $this->privilegeRepository->expects($this->any())
            ->method('getPermissionNames')
            ->with($this->isType('array'))
            ->willReturnCallback([$this, 'getPermissionNames']);

        $expectedForm = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(AccountUserRoleType::NAME, $role, ['privilege_config' => $expectedConfig])
            ->willReturn($expectedForm);

        $actualForm = $this->handler->createForm($role);
        $this->assertEquals($expectedForm, $actualForm);
        $this->assertAttributeEquals($expectedForm, 'form', $this->handler);
    }

    /**
     * @param array $types
     * @return array
     */
    public function getPermissionNames(array $types)
    {
        $names = [];
        foreach ($types as $type) {
            if (isset($this->permissionNames[$type])) {
                $names = array_merge($names, $this->permissionNames[$type]);
            }
        }

        return $names;
    }

    public function testSetRolePrivileges()
    {
        $role = new AccountUserRole('TEST');
        $roleSecurityIdentity = new RoleSecurityIdentity($role);

        $firstClass = 'FirstClass';
        $secondClass = 'SecondClass';
        $unknownClass = 'UnknownClass';

        $request = new Request();
        $request->setMethod('GET');

        $firstEntityPrivilege = $this->createPrivilege('entity', 'entity:' . $firstClass, 'VIEW');
        $firstEntityConfig = $this->createClassConfigMock(true);

        $secondEntityPrivilege = $this->createPrivilege('entity', 'entity:' . $secondClass, 'VIEW');
        $secondEntityConfig = $this->createClassConfigMock(false);

        $unknownEntityPrivilege = $this->createPrivilege('entity', 'entity:' . $unknownClass, 'VIEW');

        $actionPrivilege = $this->createPrivilege('action', 'action', 'random_action');

        $entityForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $entityForm->expects($this->once())
            ->method('setData')
            ->willReturnCallback(
                function (ArrayCollection $actualPrivileges) use ($firstEntityPrivilege) {
                    $this->assertEquals([$firstEntityPrivilege], array_values($actualPrivileges->toArray()));
                }
            );

        $actionForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $actionForm->expects($this->once())
            ->method('setData')
            ->willReturnCallback(
                function (ArrayCollection $actualPrivileges) use ($actionPrivilege) {
                    $this->assertEquals([$actionPrivilege], array_values($actualPrivileges->toArray()));
                }
            );

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['entity', $entityForm],
                    ['action', $actionForm],
                ]
            );

        $this->formFactory->expects($this->once())
            ->method('create')
            ->willReturn($form);

        $this->chainMetadataProvider->expects($this->once())
            ->method('startProviderEmulation')
            ->with(FrontendOwnershipMetadataProvider::ALIAS);
        $this->chainMetadataProvider->expects($this->once())
            ->method('stopProviderEmulation');

        $this->aclManager->expects($this->any())
            ->method('getSid')
            ->with($role)
            ->willReturn($roleSecurityIdentity);

        $this->privilegeRepository->expects($this->any())
            ->method('getPrivileges')
            ->with($roleSecurityIdentity)
            ->willReturn(
                new ArrayCollection(
                    [$firstEntityPrivilege, $secondEntityPrivilege, $unknownEntityPrivilege, $actionPrivilege]
                )
            );

        $this->ownershipConfigProvider->expects($this->any())
            ->method('hasConfig')
            ->willReturnMap(
                [
                    [$firstClass, null, true],
                    [$secondClass, null, true],
                    [$unknownClass, null, false],
                ]
            );
        $this->ownershipConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap(
                [
                    [$firstClass, null, $firstEntityConfig],
                    [$secondClass, null, $secondEntityConfig],
                ]
            );

        $this->handler->setRequest($request);
        $this->handler->createForm($role);
        $this->handler->process($role);
    }

    public function testProcessPrivileges()
    {
        $request = new Request();
        $request->setMethod('POST');

        $role = new AccountUserRole('TEST');
        $roleSecurityIdentity = new RoleSecurityIdentity($role);

        $appendForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $appendForm->expects($this->once())
            ->method('getData')
            ->willReturn([]);

        $removeForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $removeForm->expects($this->once())
            ->method('getData')
            ->willReturn([]);

        $firstEntityPrivilege = $this->createPrivilege('entity', 'entity:FirstClass', 'VIEW');
        $secondEntityPrivilege = $this->createPrivilege('entity', 'entity:SecondClass', 'VIEW');

        $entityForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $entityForm->expects($this->once())
            ->method('getData')
            ->willReturn([$firstEntityPrivilege, $secondEntityPrivilege]);

        $actionPrivilege = $this->createPrivilege('action', 'action', 'random_action');

        $actionForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $actionForm->expects($this->once())
            ->method('getData')
            ->willReturn([$actionPrivilege]);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('submit')
            ->with($request);
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $form->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['appendUsers', $appendForm],
                    ['removeUsers', $removeForm],
                    ['entity', $entityForm],
                    ['action', $actionForm],
                ]
            );

        $this->formFactory->expects($this->once())
            ->method('create')
            ->willReturn($form);

        $objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with(get_class($role))
            ->willReturn($objectManager);

        $expectedFirstEntityPrivilege = $this->createPrivilege('entity', 'entity:FirstClass', 'VIEW');
        $expectedFirstEntityPrivilege->setGroup(AccountUser::SECURITY_GROUP);

        $expectedSecondEntityPrivilege = $this->createPrivilege('entity', 'entity:SecondClass', 'VIEW');
        $expectedSecondEntityPrivilege->setGroup(AccountUser::SECURITY_GROUP);

        $expectedActionPrivilege = $this->createPrivilege('action', 'action', 'random_action');
        $expectedActionPrivilege->setGroup(AccountUser::SECURITY_GROUP);

        $this->privilegeRepository->expects($this->once())
            ->method('savePrivileges')
            ->with(
                $roleSecurityIdentity,
                new ArrayCollection(
                    [$expectedFirstEntityPrivilege, $expectedSecondEntityPrivilege, $expectedActionPrivilege]
                )
            );

        $this->aclManager->expects($this->any())
            ->method('getSid')
            ->with($role)
            ->willReturn($roleSecurityIdentity);

        $this->chainMetadataProvider->expects($this->once())
            ->method('startProviderEmulation')
            ->with(FrontendOwnershipMetadataProvider::ALIAS);
        $this->chainMetadataProvider->expects($this->once())
            ->method('stopProviderEmulation');

        $handler = new AccountUserRoleHandler($this->formFactory, $this->privilegeConfig);
        $handler->setManagerRegistry($this->managerRegistry);
        $handler->setAclPrivilegeRepository($this->privilegeRepository);
        $handler->setAclManager($this->aclManager);
        $handler->setChainMetadataProvider($this->chainMetadataProvider);
        $handler->setRequest($request);
        $handler->setDoctrineHelper($this->doctrineHelper);
        $handler->createForm($role);
        $handler->process($role);
    }

    /**
     * @param AccountUserRole $role
     * @param Account|null    $newAccount
     * @param AccountUser[]   $appendUsers
     * @param AccountUser[]   $removedUsers
     * @param AccountUser[]   $assignedUsers
     * @param AccountUser[]   $expectedUsersWithRole
     * @param AccountUser[]   $expectedUsersWithoutRole
     * @param bool            $changeAccountProcessed
     * @dataProvider processWithAccountProvider
     */
    public function testProcessWithAccount(
        AccountUserRole $role,
        $newAccount,
        array $appendUsers,
        array $removedUsers,
        array $assignedUsers,
        array $expectedUsersWithRole,
        array $expectedUsersWithoutRole,
        $changeAccountProcessed = true
    ) {
        // Array of persisted users
        /** @var AccountUser[] $persistedUsers */
        $persistedUsers = [];

        $request = new Request();
        $request->setMethod('POST');

        $appendForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $appendForm->expects($this->once())
            ->method('getData')
            ->willReturn($appendUsers);

        $removeForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $removeForm->expects($this->once())
            ->method('getData')
            ->willReturn($removedUsers);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('submit')
            ->with($request)
            ->willReturnCallback(
                function () use ($role, $newAccount) {
                    $role->setAccount($newAccount);
                }
            );
        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
        $form->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['appendUsers', $appendForm],
                    ['removeUsers', $removeForm],
                ]
            );

        $this->formFactory->expects($this->once())
            ->method('create')
            ->willReturn($form);

        $objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $objectManager->expects($this->any())
            ->method('persist')
            ->willReturnCallback(
                function ($entity) use (&$persistedUsers) {
                    if ($entity instanceof AccountUser) {
                        $persistedUsers[spl_object_hash($entity)] = $entity;
                    }
                }
            );

        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with(get_class($role))
            ->willReturn($objectManager);

        $this->roleRepository->expects($changeAccountProcessed ? $this->once() : $this->never())
            ->method('getAssignedUsers')
            ->with($role)
            ->willReturn($assignedUsers);

        /** @var \PHPUnit_Framework_MockObject_MockObject|AccountUserRoleHandler $handler */
        $handler = $this->getMockBuilder('\OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserRoleHandler')
            ->setMethods(['processPrivileges'])
            ->setConstructorArgs([$this->formFactory, $this->privilegeConfig])
            ->getMock();

        $handler->setManagerRegistry($this->managerRegistry);
        $handler->setAclPrivilegeRepository($this->privilegeRepository);
        $handler->setAclManager($this->aclManager);
        $handler->setChainMetadataProvider($this->chainMetadataProvider);
        $handler->setRequest($request);
        $handler->setDoctrineHelper($this->doctrineHelper);
        $handler->createForm($role);
        $handler->process($role);

        foreach ($expectedUsersWithRole as $expectedUser) {
            $this->assertEquals($persistedUsers[spl_object_hash($expectedUser)]->getRole($role->getRole()), $role);
        }

        foreach ($expectedUsersWithoutRole as $expectedUser) {
            $this->assertEquals($persistedUsers[spl_object_hash($expectedUser)]->getRole($role->getRole()), null);
        }
    }

    /**
     * @return array
     */
    public function processWithAccountProvider()
    {
        $newAccount1 = new Account();
        $role1 = $this->createAccountUserRole('test role1', 1);
        $role1->setAccount(null);
        $users1 = $this->createUsersWithRole($role1, 6, $newAccount1);

        $newAccount2 = new Account();
        $role2 = $this->createAccountUserRole('test role2', 2);
        $role2->setAccount(new Account());
        $users2 = $this->createUsersWithRole($role2, 6, $newAccount2);

        $role3 = $this->createAccountUserRole('test role3', 3);
        $role3->setAccount(new Account());
        $users3 = $this->createUsersWithRole($role3, 6, $role3->getAccount());

        $newAccount4 = new Account();
        $role4 = $this->createAccountUserRole('test role4', 4);
        $role4->setAccount(new Account());
        $users4 = $this->createUsersWithRole($role4, 6, $newAccount4);

        $newAccount5 = new Account();
        $role5 = $this->createAccountUserRole('test role5');
        $role5->setAccount(new Account());
        $users5 = $this->createUsersWithRole($role5, 6, $newAccount4);

        return [
            'set account for role without account (assigned users should be removed except appendUsers)'      => [
                'role'                     => $role1,
                'newAccount'               => $newAccount1,
                'appendUsers'              => [$users1[0], $users1[4], $users1[5]],
                'removedUsers'             => [$users1[2], $users1[3]],
                'assignedUsers'            => [$users1[0], $users1[1], $users1[2], $users1[3]],
                'expectedUsersWithRole'    => [$users1[4], $users1[5]], // $users0 not changed, because already has role
                'expectedUsersWithoutRole' => [$users1[1], $users1[2], $users1[3]],
            ],
            'set another account for role with account (assigned users should be removed except appendUsers)' => [
                'role'                     => $role2,
                'newAccount'               => $newAccount2,
                'appendUsers'              => [$users2[0], $users2[4], $users2[5]],
                'removedUsers'             => [$users2[2], $users2[3]],
                'assignedUsers'            => [$users2[0], $users2[1], $users2[2], $users2[3]],
                'expectedUsersWithRole'    => [$users2[4], $users2[5]], // $users0 not changed, because already has role
                'expectedUsersWithoutRole' => [$users2[1], $users2[2], $users2[3]],
            ],
            'add/remove users for role with account (account not changed)'                                    => [
                'role'                     => $role3,
                'newAccount'               => $role3->getAccount(),
                'appendUsers'              => [$users3[4], $users3[5]],
                'removedUsers'             => [$users3[2], $users3[3]],
                'assignedUsers'            => [$users3[0], $users3[1], $users3[2], $users3[3]],
                'expectedUsersWithRole'    => [$users3[4], $users3[5]],
                'expectedUsersWithoutRole' => [$users3[2], $users3[3]],
                'changeAccountProcessed'   => false,
            ],
            'remove account for role with account (assigned users should not be removed)'                     => [
                'role'                     => $role4,
                'newAccount'               => $newAccount4,
                'appendUsers'              => [$users4[0], $users4[4], $users4[5]],
                'removedUsers'             => [$users4[2], $users4[3]],
                'assignedUsers'            => [$users4[0], $users4[1], $users4[2], $users4[3]],
                'expectedUsersWithRole'    => [$users4[4], $users4[5]],
                'expectedUsersWithoutRole' => [$users4[2], $users4[3]],
            ],
            'change account logic shouldn\'t be processed (role without ID)'                                  => [
                'role'                     => $role5,
                'newAccount'               => $newAccount5,
                'appendUsers'              => [$users5[0], $users5[4], $users5[5]],
                'removedUsers'             => [$users5[2], $users5[3]],
                'assignedUsers'            => [$users5[0], $users5[1], $users5[2], $users5[3]],
                'expectedUsersWithRole'    => [$users5[4], $users5[5]],
                'expectedUsersWithoutRole' => [$users5[2], $users5[3]],
                'changeAccountProcessed'   => false,
            ],
        ];
    }

    /**
     * @param AccountUserRole $role
     * @param int             $numberOfUsers
     * @param Account         $account
     * @return \OroB2B\Bundle\AccountBundle\Entity\AccountUser[]
     */
    protected function createUsersWithRole(AccountUserRole $role, $numberOfUsers, Account $account = null)
    {
        /** @var AccountUser[] $users */
        $users = [];
        for ($i = 0; $i < $numberOfUsers; $i++) {
            $user = new AccountUser();
            $user->setUsername('user' . $i . $role->getRole());
            $user->setRoles([$role]);
            $user->setAccount($account);
            $users[] = $user;
        }

        return $users;
    }

    /**
     * @param string   $role
     * @param int|null $id
     * @return AccountUserRole
     */
    protected function createAccountUserRole($role, $id = null)
    {
        $entity = new AccountUserRole($role);
        if ($id) {
            $reflection = new \ReflectionProperty(get_class($entity), 'id');
            $reflection->setAccessible(true);
            $reflection->setValue($entity, $id);
        }

        return $entity;
    }

    /**
     * @param string $extensionKey
     * @param string $id
     * @param string $name
     * @return AclPrivilege
     */
    protected function createPrivilege($extensionKey, $id, $name)
    {
        $privilege = new AclPrivilege();
        $privilege->setExtensionKey($extensionKey);
        $privilege->setIdentity(new AclPrivilegeIdentity($id, $name));

        return $privilege;
    }

    /**
     * @param bool $hasFrontendOwner
     * @return ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createClassConfigMock($hasFrontendOwner)
    {
        $config = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $config->expects($this->any())
            ->method('has')
            ->with('frontend_owner_type')
            ->willReturn($hasFrontendOwner);

        return $config;
    }
}
