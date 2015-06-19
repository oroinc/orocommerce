<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Form\Handler;

use OroB2B\Bundle\CustomerBundle\Form\Handler\AccountUserPasswordResetHandler;

class AccountUserPasswordResetHandlerTest extends AbstractAccountUserPasswordHandlerTestCase
{
    /**
     * @var AccountUserPasswordResetHandler
     */
    protected $handler;

    protected function setUp()
    {
        parent::setUp();

        $this->handler = new AccountUserPasswordResetHandler($this->userManager, $this->translator, $this->ttl);
    }

    public function testProcess()
    {
        $user = $this->getMockBuilder('OroB2B\Bundle\CustomerBundle\Entity\AccountUser')
            ->disableOriginalConstructor()
            ->getMock();

        $this->form->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($user));

        $user->expects($this->once())
            ->method('setConfirmationToken')
            ->with(null)
            ->will($this->returnSelf());

        $user->expects($this->once())
            ->method('setPasswordRequestedAt')
            ->with(null)
            ->will($this->returnSelf());

        $user->expects($this->once())
            ->method('setConfirmed')
            ->with(true)
            ->will($this->returnSelf());

        $this->userManager->expects($this->once())
            ->method('updateUser')
            ->with($user);

        $this->assertValidForm();

        $this->assertTrue($this->handler->process($this->form, $this->request));
    }
}
