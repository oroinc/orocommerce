<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Handler;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserRoleType;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserRoleUpdateHandler;
use OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class AccountUserRoleUpdateHandlerTest extends AbstractAccountUserRoleUpdateHandlerTestCase
{
    use EntityTrait;

    protected function setUp()
    {
        parent::setUp();

        $this->handler = new AccountUserRoleUpdateHandler($this->formFactory, $this->aclCache, $this->privilegeConfig);
        $this->setRequirementsForHandler($this->handler);
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSetRolePrivileges()
    {
        $role = new AccountUserRole('TEST');
        $roleSecurityIdentity = new RoleSecurityIdentity($role);

        $firstClass = 'FirstClass';
        $secondClass = 'SecondClass';
        $unknownClass = 'UnknownClass';

        $request = new Request();
        $request->setMethod('GET');

        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $firstEntityPrivilege = $this->createPrivilege('entity', 'entity:' . $firstClass, 'VIEW', true);
        $firstEntityConfig = $this->createClassConfigMock(true);

        $secondEntityPrivilege = $this->createPrivilege('entity', 'entity:' . $secondClass, 'VIEW', true);
        $secondEntityConfig = $this->createClassConfigMock(false);

        $unknownEntityPrivilege = $this->createPrivilege('entity', 'entity:' . $unknownClass, 'VIEW', true);

        $actionPrivilege = $this->createPrivilege('action', 'action', 'random_action', true);

        $privilegesForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $privilegesForm->expects($this->once())
            ->method('setData');

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['privileges', $privilegesForm],
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

        $this->handler->setRequestStack($requestStack);
        $this->handler->createForm($role);
        $this->handler->process($role);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessPrivileges()
    {
        $request = new Request();
        $request->setMethod('POST');

        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $role = new AccountUserRole('TEST');
        $roleSecurityIdentity = new RoleSecurityIdentity($role);

        $productObjectIdentity = new ObjectIdentity('entity', 'OroB2B\Bundle\ProductBundle\Entity\Product');

        $appendForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $appendForm->expects($this->once())
            ->method('getData')
            ->willReturn([]);

        $removeForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $removeForm->expects($this->once())
            ->method('getData')
            ->willReturn([]);

        $entityForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $actionForm = $this->getMock('Symfony\Component\Form\FormInterface');

        $privilegesData = json_encode([
            'entity' => [
                0 => [
                    'identity' => [
                        'id' =>'entity:FirstClass',
                        'name' => 'VIEW',
                    ],
                    'permissions' => [
                        'VIEW' => [
                            'accessLevel' => 5,
                            'name' => 'VIEW',
                        ],
                    ],
                ],
                1 => [
                    'identity' => [
                        'id' =>'entity:SecondClass',
                        'name' => 'VIEW',
                    ],
                    'permissions' => [
                        'VIEW' => [
                            'accessLevel' => 5,
                            'name' => 'VIEW',
                        ],
                    ],
                ]
            ],
            'action' => [
                0 => [
                    'identity' => [
                        'id' =>'action',
                        'name' => 'random_action',
                    ],
                    'permissions' => [
                        'random_action' => [
                            'accessLevel' => 5,
                            'name' => 'random_action',
                        ],
                    ],
                ],
            ],
        ]);
        $privilegesForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $privilegesForm->expects($this->once())
            ->method('getData')
            ->willReturn($privilegesData);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('submit');
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
                    ['privileges', $privilegesForm],
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

        $this->aclManager->expects($this->any())
            ->method('getOid')
            ->with($productObjectIdentity->getIdentifier() . ':' . $productObjectIdentity->getType())
            ->willReturn($productObjectIdentity);

        $this->aclManager->expects($this->once())
            ->method('setPermission')
            ->with($roleSecurityIdentity, $productObjectIdentity, 16);

        /** @var \PHPUnit_Framework_MockObject_MockObject|AclExtensionInterface $aclExtension */
        $aclExtension = $this->getMock('Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface');
        $aclExtension->expects($this->once())
            ->method('getMaskBuilder')
            ->with('VIEW')
            ->willReturn(new EntityMaskBuilder(0, ['VIEW', 'CREATE', 'EDIT']));

        /** @var \PHPUnit_Framework_MockObject_MockObject|AclExtensionSelector $aclExtension */
        $aclExtensionSelector = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector')
            ->disableOriginalConstructor()
            ->getMock();
        $aclExtensionSelector->expects($this->once())
            ->method('select')
            ->with('entity:OroB2B\Bundle\ProductBundle\Entity\Product')
            ->willReturn($aclExtension);

        $this->aclManager->expects($this->once())
            ->method('getExtensionSelector')
            ->willReturn($aclExtensionSelector);

        $this->chainMetadataProvider->expects($this->once())
            ->method('startProviderEmulation')
            ->with(FrontendOwnershipMetadataProvider::ALIAS);
        $this->chainMetadataProvider->expects($this->once())
            ->method('stopProviderEmulation');

        $handler = new AccountUserRoleUpdateHandler($this->formFactory, $this->aclCache, $this->privilegeConfig);

        $this->setRequirementsForHandler($handler);
        $handler->setRequestStack($requestStack);

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

        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $this->setUpMocksForProcessWithAccount(
            $role,
            $request,
            $appendUsers,
            $removedUsers,
            $assignedUsers,
            $newAccount,
            $changeAccountProcessed
        );

        // Array of persisted users
        /** @var AccountUser[] $persistedUsers */
        $persistedUsers = [];

        $objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $objectManager->expects($this->any())
            ->method('persist')
            ->willReturnCallback(
                function ($entity) use (&$persistedUsers) {
                    if ($entity instanceof AccountUser) {
                        $persistedUsers[$entity->getEmail()] = $entity;
                    }
                }
            );

        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with(get_class($role))
            ->willReturn($objectManager);

        /** @var \PHPUnit_Framework_MockObject_MockObject|AccountUserRoleUpdateHandler $handler */
        $handler = $this->getMockBuilder('\OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserRoleUpdateHandler')
            ->setMethods(['processPrivileges'])
            ->setConstructorArgs([$this->formFactory, $this->aclCache, $this->privilegeConfig])
            ->getMock();

        $this->setRequirementsForHandler($handler);
        $handler->setRequestStack($requestStack);

        $handler->createForm($role);
        $handler->process($role);

        foreach ($expectedUsersWithRole as $expectedUser) {
            $this->assertContains($expectedUser->getEmail(), $persistedUsers, $expectedUser->getUsername());
            $this->assertEquals($persistedUsers[$expectedUser->getEmail()]->getRole($role->getRole()), $role);
        }

        foreach ($expectedUsersWithoutRole as $expectedUser) {
            $this->assertContains($expectedUser->getEmail(), $persistedUsers, $expectedUser->getUsername());
            $this->assertEquals($persistedUsers[$expectedUser->getEmail()]->getRole($role->getRole()), null);
        }
    }

    /**
     * @return array
     */
    public function processWithAccountProvider()
    {
        $oldAccount = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 1]);
        $newAccount1 = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 10]);
        $role1 = $this->createAccountUserRole('test role1', 1);
        $users1 =
            $this->createUsersWithRole($role1, 6, $newAccount1) + $this->createUsersWithRole($role1, 2, $oldAccount, 6);

        $newAccount2 = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 20]);
        $oldAcc2 = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 21]);
        $role2 = $this->createAccountUserRole('test role2', 2);
        $role2->setAccount($oldAcc2);
        $users2 =
            $this->createUsersWithRole($role2, 6, $newAccount2) + $this->createUsersWithRole($role2, 2, $oldAcc2, 6);

        $role3 = $this->createAccountUserRole('test role3', 3);
        $role3->setAccount($this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 31]));
        $users3 = $this->createUsersWithRole($role3, 6, $role3->getAccount());

        $newAccount4 = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 41]);
        $role4 = $this->createAccountUserRole('test role4', 4);
        $role4->setAccount($this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 40]));
        $users4 = $this->createUsersWithRole($role4, 6, $newAccount4);

        $newAccount5 = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 50]);
        $role5 = $this->createAccountUserRole('test role5');
        $role5->setAccount($this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 51]));
        $users5 = $this->createUsersWithRole($role5, 6, $newAccount5);

        return [
            'set account for role without account (assigned users should be removed except appendUsers)'      => [
                'role'                     => $role1,
                'newAccount'               => $newAccount1,
                'appendUsers'              => [$users1[1], $users1[5], $users1[6]],
                'removedUsers'             => [$users1[3], $users1[4]],
                'assignedUsers'            => [$users1[1], $users1[2], $users1[3], $users1[4], $users1[7]],
                'expectedUsersWithRole'    => [$users1[5], $users1[6]], // $users[1] already has role
                'expectedUsersWithoutRole' => [$users1[7], $users1[3], $users1[4]],
            ],
            'set another account for role with account (assigned users should be removed except appendUsers)' => [
                'role'                     => $role2,
                'newAccount'               => $newAccount2,
                'appendUsers'              => [$users2[1], $users2[5], $users2[6]],
                'removedUsers'             => [$users2[3], $users2[4]],
                'assignedUsers'            => [$users2[1], $users2[2], $users2[3], $users2[4], $users1[7], $users1[8]],
                'expectedUsersWithRole'    => [$users2[5], $users2[6]], // $users0 not changed, because already has role
                'expectedUsersWithoutRole' => [$users1[7], $users1[8], $users2[3], $users2[4]],
            ],
            'add/remove users for role with account (account not changed)'                                    => [
                'role'                     => $role3,
                'newAccount'               => $role3->getAccount(),
                'appendUsers'              => [$users3[5], $users3[6]],
                'removedUsers'             => [$users3[3], $users3[4]],
                'assignedUsers'            => [$users3[1], $users3[2], $users3[3], $users3[4]],
                'expectedUsersWithRole'    => [$users3[5], $users3[6]],
                'expectedUsersWithoutRole' => [$users3[3], $users3[4]],
                'changeAccountProcessed'   => false,
            ],
            'remove account for role with account (assigned users should not be removed)'                     => [
                'role'                     => $role4,
                'newAccount'               => $newAccount4,
                'appendUsers'              => [$users4[1], $users4[5], $users4[6]],
                'removedUsers'             => [$users4[3], $users4[4]],
                'assignedUsers'            => [$users4[1], $users4[2], $users4[3], $users4[4]],
                'expectedUsersWithRole'    => [$users4[5], $users4[6]],
                'expectedUsersWithoutRole' => [$users4[3], $users4[4]],
            ],
            'change account logic shouldn\'t be processed (role without ID)'                                  => [
                'role'                     => $role5,
                'newAccount'               => $newAccount5,
                'appendUsers'              => [$users5[1], $users5[5], $users5[6]],
                'removedUsers'             => [$users5[3], $users5[4]],
                'assignedUsers'            => [$users5[1], $users5[2], $users5[3], $users5[4]],
                'expectedUsersWithRole'    => [$users5[5], $users5[6]],
                'expectedUsersWithoutRole' => [$users5[3], $users5[4]],
                'changeAccountProcessed'   => false,
            ],
        ];
    }

    /**
     * @param string $extensionKey
     * @param string $id
     * @param string $name
     * @param bool $setExtensionKey
     * @return AclPrivilege
     */
    protected function createPrivilege($extensionKey, $id, $name, $setExtensionKey = false)
    {
        $privilege = new AclPrivilege();
        if ($setExtensionKey) {
            $privilege->setExtensionKey($extensionKey);
        }
        $privilege->setIdentity(new AclPrivilegeIdentity($id, $name));
        $privilege->addPermission(new AclPermission($name, 5));

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

    public function testGetAccountUserRolePrivilegeConfig()
    {
        $role = new AccountUserRole();
        $this->assertInternalType('array', $this->handler->getAccountUserRolePrivilegeConfig($role));
        $this->assertEquals($this->privilegeConfig, $this->handler->getAccountUserRolePrivilegeConfig($role));
    }

    /**
     * @param ArrayCollection $privileges
     * @param array $expected
     *
     * @dataProvider accountUserRolePrivilegesDataProvider
     */
    public function testGetAccountUserRolePrivileges(ArrayCollection $privileges, array $expected)
    {
        $privilegeConfig = [
            'entity' => ['types' => ['entity'], 'fix_values' => false, 'show_default' => true],
            'action' => ['types' => ['action'], 'fix_values' => false, 'show_default' => true],
            'default' => ['types' => ['(default)'], 'fix_values' => true, 'show_default' => false],
        ];
        $handler = new AccountUserRoleUpdateHandler($this->formFactory, $this->aclCache, $privilegeConfig);
        $this->setRequirementsForHandler($handler);

        $role = new AccountUserRole('ROLE_ADMIN');
        $securityIdentity = new RoleSecurityIdentity($role);
        $this->aclManager->expects($this->once())
            ->method('getSid')
            ->with($role)
            ->willReturn($securityIdentity);
        $this->privilegeRepository->expects($this->once())
            ->method('getPrivileges')
            ->with($securityIdentity)
            ->willReturn($privileges);
        $this->chainMetadataProvider->expects($this->at(0))
            ->method('startProviderEmulation')
            ->with(FrontendOwnershipMetadataProvider::ALIAS);
        $this->chainMetadataProvider->expects($this->at(1))
            ->method('stopProviderEmulation');
        $result = $handler->getAccountUserRolePrivileges($role);


        $this->assertEquals(array_keys($expected), array_keys($result));
        /**
         * @var string $key
         * @var ArrayCollection $value
         */
        foreach ($expected as $key => $value) {
            $this->assertEquals($value->getValues(), $result[$key]->getValues());
        }
    }

    /**
     * @return array
     */
    public function accountUserRolePrivilegesDataProvider()
    {
        $privilegesForEntity = [
            ['VIEW', 2],
            ['CREATE', 2],
            ['EDIT', 2],
            ['DELETE', 2],
            ['SHARE', 2],
        ];
        $privilegesForEntity2 = [
            ['VIEW', 222],
            ['CREATE', 2],
            ['EDIT', 2],
            ['DELETE', 2],
            ['SHARE', 2],
        ];
        $privilegesForAction = [
            ['EXECUTE', 5],
        ];
        return [
            'get and sorted privileges' => [
                'privileges' => $this->createPrivileges(
                    [
                        [
                            'total' => 10,
                            'extensionKey' => 'entity',
                            'identityName' => null,
                            'aclPermissions' => $privilegesForEntity,
                        ],
                        [
                            'total' => 5,
                            'extensionKey' => 'action',
                            'identityName' => null,
                            'aclPermissions' => $privilegesForAction,
                        ],
                        [
                            'total' => 3,
                            'extensionKey' => 'testExtension',
                            'identityName' => null,
                            'aclPermissions' => $privilegesForEntity,
                        ],
                        [
                            'total' => 2,
                            'extensionKey' => '(default)',
                            'identityName' => '(default)',
                            'aclPermissions' => $privilegesForEntity,
                        ],
                        [
                            'total' => 1,
                            'extensionKey' => '(default)',
                            'identityName' => null,
                            'aclPermissions' => $privilegesForEntity,
                        ],
                    ]
                ),
                'expected' => [
                    'entity' => $this->createPrivileges([
                        [
                            'total' => 10,
                            'extensionKey' => 'entity',
                            'identityName' => null,
                            'aclPermissions' => $privilegesForEntity,
                        ],
                    ]),
                    'action' => $this->createPrivileges([
                        [
                            'total' => 5,
                            'extensionKey' => 'action',
                            'identityName' => null,
                            'aclPermissions' => $privilegesForAction,
                        ],
                    ]),
                    'default' => $this->createPrivileges([
                        [
                            'total' => 1,
                            'extensionKey' => '(default)',
                            'identityName' => null,
                            'aclPermissions' => $privilegesForEntity2,
                        ],
                    ]),
                ],
            ],
        ];
    }
    /**
     * @param array $config
     * @return array
     */
    protected function createPrivileges(array $config)
    {
        $privileges = new ArrayCollection();
        foreach ($config as $value) {
            for ($i = 1; $i <= $value['total']; $i++) {
                $privilege = new AclPrivilege();
                $privilege->setExtensionKey($value['extensionKey']);
                $identityName = $value['identityName'] ?: 'EntityClass_' . $i;
                $privilege->setIdentity(new AclPrivilegeIdentity($i, $identityName));
                $privilege->setGroup('commerce');
                foreach ($value['aclPermissions'] as $aclPermission) {
                    list($name, $accessLevel) = $aclPermission;
                    $privilege->addPermission(new AclPermission($name, $accessLevel));
                }
                $privileges->add($privilege);
            }
        }
        return $privileges;
    }

    /**
     * @param AccountUserRole $role
     * @param Request         $request
     * @param array           $appendUsers
     * @param array           $removedUsers
     * @param array           $assignedUsers
     * @param Account|null    $newAccount
     * @param bool            $changeAccountProcessed
     */
    protected function setUpMocksForProcessWithAccount(
        AccountUserRole $role,
        Request $request,
        array $appendUsers,
        array $removedUsers,
        array $assignedUsers,
        $newAccount,
        $changeAccountProcessed
    ) {
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
            ->willReturnCallback(
                function () use ($role, $newAccount) {
                    $role->setAccount($newAccount);
                    $role->setOrganization($newAccount->getOrganization());
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

        $this->roleRepository->expects($changeAccountProcessed ? $this->once() : $this->never())
            ->method('getAssignedUsers')
            ->with($role)
            ->willReturn($assignedUsers);
    }
}
