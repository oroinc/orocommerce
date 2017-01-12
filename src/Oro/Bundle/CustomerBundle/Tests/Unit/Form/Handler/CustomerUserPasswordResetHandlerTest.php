<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\CustomerBundle\Form\Handler\CustomerUserPasswordResetHandler;

class CustomerUserPasswordResetHandlerTest extends AbstractCustomerUserPasswordHandlerTestCase
{
    /**
     * @var CustomerUserPasswordResetHandler
     */
    protected $handler;

    protected function setUp()
    {
        parent::setUp();

        $this->handler = new CustomerUserPasswordResetHandler($this->userManager, $this->translator);
    }

    public function testProcess()
    {
        $user = $this->getMockBuilder('Oro\Bundle\CustomerBundle\Entity\CustomerUser')
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
