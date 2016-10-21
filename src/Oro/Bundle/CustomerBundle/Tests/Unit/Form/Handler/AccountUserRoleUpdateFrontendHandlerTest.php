<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserRole;
use Oro\Bundle\CustomerBundle\Form\Handler\AccountUserRoleUpdateFrontendHandler;

class AccountUserRoleUpdateFrontendHandlerTest extends AbstractAccountUserRoleUpdateHandlerTestCase
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
     * @param AccountUserRole $role
     * @param AccountUserRole $expectedRole
     * @param AccountUser $accountUser
     * @param AccountUserRole $expectedPredefinedRole
     *
     * @dataProvider successDataProvider
     */
    public function testOnSuccess(
        AccountUserRole $role,
        AccountUserRole $expectedRole,
        AccountUser $accountUser,
        AccountUserRole $expectedPredefinedRole = null
    ) {
        $request = new Request();
        $request->setMethod('POST');

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
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

        $handler = new AccountUserRoleUpdateFrontendHandler(
            $this->formFactory,
            $this->aclCache,
            $this->privilegeConfig
        );

        $this->setRequirementsForHandler($handler);
        $handler->setRequest($request);
        $handler->setTokenStorage($this->tokenStorage);

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())->method('getUser')->willReturn($accountUser);
        $this->tokenStorage->expects($this->any())->method('getToken')->willReturn($token);

        $handler->createForm($role);
    }

    /**
     * @return array
     */
    public function successDataProvider()
    {
        $accountUser = new AccountUser();
        $account = new Account();
        $accountUser->setAccount($account);

        return [
            'edit predefined role should pass it to from and' => [
                (new AccountUserRole()),
                (new AccountUserRole())->setAccount($account),
                $accountUser,
                (new AccountUserRole()),
            ],
            'edit account role should not pass predefined role to form' => [

                (new AccountUserRole())->setAccount($account),
                (new AccountUserRole())->setAccount($account),
                $accountUser,
            ],
        ];
    }

    /**
     * @param AccountUserRole $role
     * @param AccountUserRole $expectedRole
     * @param AccountUser $accountUser
     * @param array $existingPrivileges

     * @dataProvider successDataPrivilegesProvider
     */
    public function testOnSuccessSetPrivileges(
        AccountUserRole $role,
        AccountUserRole $expectedRole,
        AccountUser $accountUser,
        array $existingPrivileges
    ) {
        $request = new Request();
        $request->setMethod('GET');

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->formFactory->expects($this->once())->method('create')->willReturn($form);

        $handler = new AccountUserRoleUpdateFrontendHandler(
            $this->formFactory,
            $this->aclCache,
            $this->privilegeConfig
        );

        $this->setRequirementsForHandler($handler);
        $handler->setRequest($request);
        $handler->setTokenStorage($this->tokenStorage);

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
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

        $privilegesForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $privilegesForm->expects($this->any())
            ->method('setData');
        $form->expects($this->any())->method('get')
            ->willReturn($privilegesForm);

        $metadata = $this->getMock('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface');
        $metadata->expects($this->exactly(2))->method('hasOwner')->willReturnOnConsecutiveCalls(true, false);
        $this->chainMetadataProvider->expects($this->any())->method('getMetadata')->willReturn($metadata);

        $handler->process($role);
    }

    /**
     * @return array
     */
    public function successDataPrivilegesProvider()
    {
        $accountUser = new AccountUser();
        $account = new Account();
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
                (new AccountUserRole()),
                (new AccountUserRole()),
                $accountUser,
                ['valid' => $privilege, 'action' => $privilege2, 'no_owner' => $privilege3, 'root' => $privilege4],
            ],
            'edit account role should use own privileges' => [
                (new AccountUserRole())->setAccount($account),
                (new AccountUserRole())->setAccount($account),
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

        $handler = new AccountUserRoleUpdateFrontendHandler(
            $this->formFactory,
            $this->aclCache,
            $this->privilegeConfig
        );

        $this->setRequirementsForHandler($handler);
        $handler->setRequest($request);
        $handler->setTokenStorage($this->tokenStorage);

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())->method('getUser')->willReturn(new \stdClass());
        $this->tokenStorage->expects($this->any())->method('getToken')->willReturn($token);

        $handler->createForm(new AccountUserRole());
    }
}
