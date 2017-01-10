<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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

    protected function setUp()
    {
        parent::setUp();

        $this->tokenStorage = $this
            ->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param CustomerUserRole $role
     * @param CustomerUserRole $expectedRole
     * @param CustomerUser $accountUser
     * @param CustomerUserRole $expectedPredefinedRole
     *
     * @dataProvider successDataProvider
     */
    public function testOnSuccess(
        CustomerUserRole $role,
        CustomerUserRole $expectedRole,
        CustomerUser $accountUser,
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

        $handler = new CustomerUserRoleUpdateFrontendHandler(
            $this->formFactory,
            $this->aclCache,
            $this->privilegeConfig
        );

        $this->setRequirementsForHandler($handler);
        $handler->setRequest($request);
        $handler->setTokenStorage($this->tokenStorage);

        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())->method('getUser')->willReturn($accountUser);
        $this->tokenStorage->expects($this->any())->method('getToken')->willReturn($token);

        $handler->createForm($role);
    }

    /**
     * @return array
     */
    public function successDataProvider()
    {
        $accountUser = new CustomerUser();
        $account = new Customer();
        $accountUser->setAccount($account);

        return [
            'edit predefined role should pass it to from and' => [
                (new CustomerUserRole()),
                (new CustomerUserRole())->setAccount($account),
                $accountUser,
                (new CustomerUserRole()),
            ],
            'edit account role should not pass predefined role to form' => [

                (new CustomerUserRole())->setAccount($account),
                (new CustomerUserRole())->setAccount($account),
                $accountUser,
            ],
        ];
    }

    /**
     * @param CustomerUserRole $role
     * @param CustomerUserRole $expectedRole
     * @param CustomerUser $accountUser
     * @param array $existingPrivileges

     * @dataProvider successDataPrivilegesProvider
     */
    public function testOnSuccessSetPrivileges(
        CustomerUserRole $role,
        CustomerUserRole $expectedRole,
        CustomerUser $accountUser,
        array $existingPrivileges
    ) {
        $request = new Request();
        $request->setMethod('GET');

        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $this->formFactory->expects($this->once())->method('create')->willReturn($form);

        $handler = new CustomerUserRoleUpdateFrontendHandler(
            $this->formFactory,
            $this->aclCache,
            $this->privilegeConfig
        );

        $this->setRequirementsForHandler($handler);
        $handler->setRequest($request);
        $handler->setTokenStorage($this->tokenStorage);

        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())->method('getUser')->willReturn($accountUser);
        $this->tokenStorage->expects($this->any())->method('getToken')->willReturn($token);

        $handler->createForm($role);

        $roleSecurityIdentity = new RoleSecurityIdentity($expectedRole);
        $privilegeCollection = new ArrayCollection($existingPrivileges);

        $this->privilegeRepository->expects($this->any())
            ->method('getPrivileges')
            ->with($roleSecurityIdentity)
            ->willReturn($privilegeCollection);

        $this->aclManager->expects($this->once())->method('getSid')->with($expectedRole)
            ->willReturn($roleSecurityIdentity);

        $this->ownershipConfigProvider->expects($this->exactly(3))->method('hasConfig')->willReturn(true);

        $privilegesForm = $this->createMock('Symfony\Component\Form\FormInterface');
        $privilegesForm->expects($this->any())
            ->method('setData');
        $form->expects($this->any())->method('get')
            ->willReturn($privilegesForm);

        $metadata = $this->createMock('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface');
        $metadata->expects($this->exactly(2))->method('hasOwner')->willReturnOnConsecutiveCalls(true, false);
        $this->chainMetadataProvider->expects($this->any())->method('getMetadata')->willReturn($metadata);

        $handler->process($role);
    }

    /**
     * @return array
     */
    public function successDataPrivilegesProvider()
    {
        $accountUser = new CustomerUser();
        $account = new Customer();
        $accountUser->setAccount($account);

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
                $accountUser,
                ['valid' => $privilege, 'action' => $privilege2, 'no_owner' => $privilege3, 'root' => $privilege4],
            ],
            'edit account role should use own privileges' => [
                (new CustomerUserRole())->setAccount($account),
                (new CustomerUserRole())->setAccount($account),
                $accountUser,
                ['valid' => $privilege, 'action' => $privilege2, 'no_owner' => $privilege3, 'root' => $privilege4],
            ],
        ];
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testMissingAccountUser()
    {
        $request = new Request();
        $request->setMethod('POST');

        $handler = new CustomerUserRoleUpdateFrontendHandler(
            $this->formFactory,
            $this->aclCache,
            $this->privilegeConfig
        );

        $this->setRequirementsForHandler($handler);
        $handler->setRequest($request);
        $handler->setTokenStorage($this->tokenStorage);

        $token = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())->method('getUser')->willReturn(new \stdClass());
        $this->tokenStorage->expects($this->any())->method('getToken')->willReturn($token);

        $handler->createForm(new CustomerUserRole());
    }
}
