<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserRoleType;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Form\Handler\CustomerUserRoleUpdateHandler;
use Oro\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CustomerUserRoleUpdateHandlerTest extends AbstractCustomerUserRoleUpdateHandlerTestCase
{
    /**
     * @var CustomerUserRoleUpdateHandler
     */
    protected $handler;

    /** {@inheritdoc} */
    protected function setUp()
    {
        parent::setUp();

        $this->handler = $this->getHandler();
        $this->setRequirementsForHandler($this->handler);
    }

    /** {@inheritdoc} */
    protected function getHandler()
    {
        if (!$this->handler) {
            $this->handler = new CustomerUserRoleUpdateHandler(
                $this->formFactory,
                $this->aclCache,
                $this->privilegeConfig
            );
        }

        return $this->handler;
    }

    public function testCreateForm()
    {
        $role = new CustomerUserRole('TEST');

        $expectedConfig = $this->privilegeConfig;
        foreach ($expectedConfig as $key => $value) {
            $expectedConfig[$key]['permissions'] = $this->getPermissionNames($value['types']);
        }

        $this->privilegeRepository->expects($this->any())
            ->method('getPermissionNames')
            ->with($this->isType('array'))
            ->willReturnCallback([$this, 'getPermissionNames']);

        $expectedForm = $this->createMock(FormInterface::class);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CustomerUserRoleType::NAME, $role, ['privilege_config' => $expectedConfig])
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
        $role = new CustomerUserRole('TEST');
        $roleSecurityIdentity = new RoleSecurityIdentity($role);

        $firstClass = 'FirstClass';
        $secondClass = 'SecondClass';
        $unknownClass = 'UnknownClass';

        $request = new Request();
        $request->setMethod('GET');

        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $firstEntityPrivilege = $this->createPrivilege('entity', 'entity:' . $firstClass, 'VIEW', true);
        $firstEntityConfig = $this->createClassConfigMock(true);

        $secondEntityPrivilege = $this->createPrivilege('entity', 'entity:' . $secondClass, 'VIEW', true);
        $secondEntityConfig = $this->createClassConfigMock(false);

        $unknownEntityPrivilege = $this->createPrivilege('entity', 'entity:' . $unknownClass, 'VIEW', true);

        $actionPrivilege = $this->createPrivilege('action', 'action', 'random_action', true);

        $privilegesForm = $this->createMock(FormInterface::class);
        $privilegesForm->expects($this->once())
            ->method('setData');

        $form = $this->createMock(FormInterface::class);
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
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $role = new CustomerUserRole('TEST');
        $roleSecurityIdentity = new RoleSecurityIdentity($role);

        $productObjectIdentity = new ObjectIdentity('entity', Product::class);

        $appendForm = $this->createMock(FormInterface::class);
        $appendForm->expects($this->once())
            ->method('getData')
            ->willReturn([]);

        $removeForm = $this->createMock(FormInterface::class);
        $removeForm->expects($this->once())
            ->method('getData')
            ->willReturn([]);

        $entityForm = $this->createMock(FormInterface::class);
        $actionForm = $this->createMock(FormInterface::class);

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
        $privilegesForm = $this->createMock(FormInterface::class);
        $privilegesForm->expects($this->once())
            ->method('getData')
            ->willReturn($privilegesData);

        $form = $this->createMock(FormInterface::class);
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

        $objectManager = $this->createMock(ObjectManager::class);
        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with(get_class($role))
            ->willReturn($objectManager);

        $expectedFirstEntityPrivilege = $this->createPrivilege('entity', 'entity:FirstClass', 'VIEW');
        $expectedFirstEntityPrivilege->setGroup(CustomerUser::SECURITY_GROUP);

        $expectedSecondEntityPrivilege = $this->createPrivilege('entity', 'entity:SecondClass', 'VIEW');
        $expectedSecondEntityPrivilege->setGroup(CustomerUser::SECURITY_GROUP);

        $expectedActionPrivilege = $this->createPrivilege('action', 'action', 'random_action');
        $expectedActionPrivilege->setGroup(CustomerUser::SECURITY_GROUP);

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
        $aclExtension = $this->createMock(AclExtensionInterface::class);
        $aclExtension->expects($this->once())
            ->method('getMaskBuilder')
            ->with('VIEW')
            ->willReturn(new EntityMaskBuilder(0, ['VIEW', 'CREATE', 'EDIT']));

        /** @var \PHPUnit_Framework_MockObject_MockObject|AclExtensionSelector $aclExtension */
        $aclExtensionSelector = $this->getMockBuilder(AclExtensionSelector::class)
            ->disableOriginalConstructor()
            ->getMock();
        $aclExtensionSelector->expects($this->once())
            ->method('select')
            ->with('entity:Oro\Bundle\ProductBundle\Entity\Product')
            ->willReturn($aclExtension);

        $this->aclManager->expects($this->once())
            ->method('getExtensionSelector')
            ->willReturn($aclExtensionSelector);

        $this->chainMetadataProvider->expects($this->once())
            ->method('startProviderEmulation')
            ->with(FrontendOwnershipMetadataProvider::ALIAS);
        $this->chainMetadataProvider->expects($this->once())
            ->method('stopProviderEmulation');

        $handler = new CustomerUserRoleUpdateHandler($this->formFactory, $this->aclCache, $this->privilegeConfig);

        $this->setRequirementsForHandler($handler);
        $handler->setRequestStack($requestStack);

        $handler->createForm($role);
        $handler->process($role);
    }

    /**
     * @return array
     */
    public function processWithCustomerProvider()
    {
        /** @var CustomerUser[] $users */
        /** @var CustomerUserRole[] $roles */
        /** @var Customer[] $customers */
        list(
            $users,
            $roles,
            $customers
        ) = $this->prepareUsersAndRoles();

        list($users1, $users2, $users3, $users4, $users5) = $users;
        list($role1, $role2, $role3, $role4, $role5) = $roles;
        list($newCustomer1, $newCustomer2, $newCustomer4, $newCustomer5) = $customers;

        return [
            'set customer for role without customer (assigned users should be removed except appendUsers)' => [
                'role'                     => $role1,
                'newCustomer'              => $newCustomer1,
                'appendUsers'              => [$users1[1], $users1[5], $users1[6]],
                'removedUsers'             => [$users1[3], $users1[4]],
                'assignedUsers'            => [$users1[1], $users1[2], $users1[3], $users1[4], $users1[7]],
                'expectedUsersWithRole'    => [$users1[5], $users1[6]], // $users[1] already has role
                'expectedUsersWithoutRole' => [$users1[7], $users1[3], $users1[4]],
            ],
            'set another customer for role with customer (assigned users should be removed except appendUsers)' => [
                'role'                     => $role2,
                'newCustomer'              => $newCustomer2,
                'appendUsers'              => [$users2[1], $users2[5], $users2[6]],
                'removedUsers'             => [$users2[3], $users2[4]],
                'assignedUsers'            => [$users2[1], $users2[2], $users2[3], $users2[4], $users1[7], $users1[8]],
                'expectedUsersWithRole'    => [$users2[5], $users2[6]], // $users0 not changed, because already has role
                'expectedUsersWithoutRole' => [$users1[7], $users1[8], $users2[3], $users2[4]],
            ],
            'add/remove users for role with customer (customer not changed)' => [
                'role'                     => $role3,
                'newCustomer'              => $role3->getCustomer(),
                'appendUsers'              => [$users3[5], $users3[6]],
                'removedUsers'             => [$users3[3], $users3[4]],
                'assignedUsers'            => [$users3[1], $users3[2], $users3[3], $users3[4]],
                'expectedUsersWithRole'    => [$users3[5], $users3[6]],
                'expectedUsersWithoutRole' => [$users3[3], $users3[4]],
                'changeCustomerProcessed'  => false,
            ],
            'remove customer for role with customer (assigned users should not be removed)' => [
                'role'                     => $role4,
                'newCustomer'              => $newCustomer4,
                'appendUsers'              => [$users4[1], $users4[5], $users4[6]],
                'removedUsers'             => [$users4[3], $users4[4]],
                'assignedUsers'            => [$users4[1], $users4[2], $users4[3], $users4[4]],
                'expectedUsersWithRole'    => [$users4[5], $users4[6]],
                'expectedUsersWithoutRole' => [$users4[3], $users4[4]],
            ],
            'change customer logic shouldn\'t be processed (role without ID)' => [
                'role'                     => $role5,
                'newCustomer'              => $newCustomer5,
                'appendUsers'              => [$users5[1], $users5[5], $users5[6]],
                'removedUsers'             => [$users5[3], $users5[4]],
                'assignedUsers'            => [$users5[1], $users5[2], $users5[3], $users5[4]],
                'expectedUsersWithRole'    => [$users5[5], $users5[6]],
                'expectedUsersWithoutRole' => [$users5[3], $users5[4]],
                'changeCustomerProcessed'  => false,
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
        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->any())
            ->method('has')
            ->with('frontend_owner_type')
            ->willReturn($hasFrontendOwner);

        return $config;
    }

    public function testGetCustomerUserRolePrivilegeConfig()
    {
        $role = new CustomerUserRole();
        $this->assertInternalType('array', $this->handler->getCustomerUserRolePrivilegeConfig($role));
        $this->assertEquals($this->privilegeConfig, $this->handler->getCustomerUserRolePrivilegeConfig($role));
    }

    /**
     * @param ArrayCollection $privileges
     * @param array $expected
     *
     * @dataProvider CustomerUserRolePrivilegesDataProvider
     */
    public function testGetCustomerUserRolePrivileges(ArrayCollection $privileges, array $expected)
    {
        $privilegeConfig = [
            'entity' => ['types' => ['entity'], 'fix_values' => false, 'show_default' => true],
            'action' => ['types' => ['action'], 'fix_values' => false, 'show_default' => true],
            'default' => ['types' => ['(default)'], 'fix_values' => true, 'show_default' => false],
        ];
        $handler = new CustomerUserRoleUpdateHandler($this->formFactory, $this->aclCache, $privilegeConfig);
        $this->setRequirementsForHandler($handler);

        $role = new CustomerUserRole('ROLE_ADMIN');
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
        $result = $handler->getCustomerUserRolePrivileges($role);


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
    public function customerUserRolePrivilegesDataProvider()
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
     * @return ArrayCollection
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
}
