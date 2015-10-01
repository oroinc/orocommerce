<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserRoleUpdateFrontendHandler;

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

        $handler = new AccountUserRoleUpdateFrontendHandler($this->formFactory, $this->privilegeConfig);

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
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testMissingAccountUser()
    {
        $request = new Request();
        $request->setMethod('POST');

        $handler = new AccountUserRoleUpdateFrontendHandler($this->formFactory, $this->privilegeConfig);

        $this->setRequirementsForHandler($handler);
        $handler->setRequest($request);
        $handler->setTokenStorage($this->tokenStorage);

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())->method('getUser')->willReturn(new \stdClass());
        $this->tokenStorage->expects($this->any())->method('getToken')->willReturn($token);

        $handler->createForm(new AccountUserRole());
    }
}
