<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Form\Handler\CustomerUserRoleUpdateFrontendHandler;

class CustomerUserRoleUpdateFrontendHandlerTest extends AbstractCustomerUserRoleUpdateHandlerTestCase
{
    /** @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $tokenStorage;

    /** @var CustomerUserRoleUpdateFrontendHandler */
    protected $handler;

    protected function setUp()
    {
        parent::setUp();

        $this->getHandler();
        $this->setRequirementsForHandler($this->handler);

        $this->tokenStorage = $this
            ->getMockBuilder(TokenStorageInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * {@inheritdoc}
     */
    protected function getHandler()
    {
        if (!$this->handler) {
            $this->handler = new CustomerUserRoleUpdateFrontendHandler(
                $this->formFactory,
                $this->aclCache,
                $this->privilegeConfig
            );
        }

        return $this->handler;
    }

    /**
     * @param CustomerUserRole $role
     * @param CustomerUserRole $expectedRole
     * @param CustomerUser $customerUser
     * @param CustomerUserRole $expectedPredefinedRole
     *
     * @dataProvider successDataProvider
     */
    public function testOnSuccess(
        CustomerUserRole $role,
        CustomerUserRole $expectedRole,
        CustomerUser $customerUser,
        CustomerUserRole $expectedPredefinedRole = null
    ) {
        $request = new Request();
        $request->setMethod('POST');

        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                $this->isType('string'),
                $this->equalTo($expectedRole),
                $this->logicalAnd(
                    $this->isType('array'),
                    $this->callback(
                        function ($options) use ($expectedPredefinedRole) {
                            $this->arrayHasKey('predefined_role');
                            $this->assertEquals($expectedPredefinedRole, $options['predefined_role']);

                            return true;
                        }
                    )
                )
            )
            ->willReturn($form);


        $this->handler->setRequest($request);
        $this->handler->setTokenStorage($this->tokenStorage);

        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())->method('getUser')->willReturn($customerUser);
        $this->tokenStorage->expects($this->any())->method('getToken')->willReturn($token);

        $this->handler->createForm($role);
    }

    /**
     * @return array
     */
    public function successDataProvider()
    {
        $customerUser = new CustomerUser();
        $customer = new Customer();
        $customerUser->setCustomer($customer);

        return [
            'edit predefined role should pass it to from and' => [
                (new CustomerUserRole()),
                (new CustomerUserRole())->setCustomer($customer),
                $customerUser,
                (new CustomerUserRole()),
            ],
            'edit customer role should not pass predefined role to form' => [

                (new CustomerUserRole())->setCustomer($customer),
                (new CustomerUserRole())->setCustomer($customer),
                $customerUser,
            ],
        ];
    }

    /**
     * @param CustomerUserRole $role
     * @param CustomerUserRole $expectedRole
     * @param CustomerUser $customerUser
     * @param array $existingPrivileges

     * @dataProvider successDataPrivilegesProvider
     */
    public function testOnSuccessSetPrivileges(
        CustomerUserRole $role,
        CustomerUserRole $expectedRole,
        CustomerUser $customerUser,
        array $existingPrivileges
    ) {
        $request = new Request();
        $request->setMethod('GET');

        $form = $this->createMock(FormInterface::class);
        $this->formFactory->expects($this->once())->method('create')->willReturn($form);

        $this->handler->setRequest($request);
        $this->handler->setTokenStorage($this->tokenStorage);

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->any())->method('getUser')->willReturn($customerUser);
        $this->tokenStorage->expects($this->any())->method('getToken')->willReturn($token);

        $this->handler->createForm($role);

        $roleSecurityIdentity = new RoleSecurityIdentity($expectedRole);
        $privilegeCollection = new ArrayCollection($existingPrivileges);

        $this->privilegeRepository->expects($this->any())
            ->method('getPrivileges')
            ->with($roleSecurityIdentity)
            ->willReturn($privilegeCollection);

        $this->aclManager->expects($this->once())->method('getSid')->with($expectedRole)
            ->willReturn($roleSecurityIdentity);

        $this->ownershipConfigProvider->expects($this->exactly(3))->method('hasConfig')->willReturn(true);

        $privilegesForm = $this->createMock(FormInterface::class);
        $privilegesForm->expects($this->any())
            ->method('setData');
        $form->expects($this->any())->method('get')
            ->willReturn($privilegesForm);

        $metadata = $this->createMock(OwnershipMetadataInterface::class);
        $metadata->expects($this->exactly(2))->method('hasOwner')->willReturnOnConsecutiveCalls(true, false);
        $this->chainMetadataProvider->expects($this->any())->method('getMetadata')->willReturn($metadata);

        $this->handler->process($role);
    }

    /**
     * @return array
     */
    public function successDataPrivilegesProvider()
    {
        $customerUser = new CustomerUser();
        $customer = new Customer();
        $customerUser->setCustomer($customer);

        $privilege = new AclPrivilege();
        $privilege->setExtensionKey('entity');
        $privilege->setIdentity(new AclPrivilegeIdentity('entity:\stdClass', 'VIEW'));

        $privilege2 = new AclPrivilege();
        $privilege2->setExtensionKey('action');
        $privilege2->setIdentity(new AclPrivilegeIdentity('action:todo', 'FULL'));

        $privilege3 = new AclPrivilege();
        $privilege3->setExtensionKey('entity');
        $privilege3->setIdentity(new AclPrivilegeIdentity('entity:\stdClassNoOwnership', 'VIEW'));

        $privilege4 = new AclPrivilege();
        $privilege4->setExtensionKey('entity');
        $privilege4->setIdentity(
            new AclPrivilegeIdentity('entity:' . ObjectIdentityFactory::ROOT_IDENTITY_TYPE, 'VIEW')
        );

        return [
            'edit predefined role should use privileges form predefined' => [
                (new CustomerUserRole()),
                (new CustomerUserRole()),
                $customerUser,
                ['valid' => $privilege, 'action' => $privilege2, 'no_owner' => $privilege3, 'root' => $privilege4],
            ],
            'edit customer role should use own privileges' => [
                (new CustomerUserRole())->setCustomer($customer),
                (new CustomerUserRole())->setCustomer($customer),
                $customerUser,
                ['valid' => $privilege, 'action' => $privilege2, 'no_owner' => $privilege3, 'root' => $privilege4],
            ],
        ];
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testMissingCustomerUser()
    {
        $request = new Request();
        $request->setMethod('POST');

        $this->handler->setRequest($request);
        $this->handler->setTokenStorage($this->tokenStorage);

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->any())->method('getUser')->willReturn(new \stdClass());
        $this->tokenStorage->expects($this->any())->method('getToken')->willReturn($token);

        $this->handler->createForm(new CustomerUserRole());
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
}
