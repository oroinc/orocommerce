<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Handler;

use OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserPasswordRequestHandler;

class AccountUserPasswordRequestHandlerTest extends AbstractAccountUserPasswordHandlerTestCase
{
    /**
     * @var AccountUserPasswordRequestHandler
     */
    protected $handler;

    protected function setUp()
    {
        parent::setUp();

        $this->handler = new AccountUserPasswordRequestHandler($this->userManager, $this->translator);
    }

    public function testProcessInvalidUser()
    {
        $email = 'test@test.com';
        $this->assertValidFormCall($email);

        $this->userManager->expects($this->once())
            ->method('findUserByUsernameOrEmail')
            ->with($email);

        $this->assertFormErrorAdded(
            $this->form,
            'orob2b.account.accountuser.profile.email_not_exists',
            ['%email%' => $email]
        );

        $this->assertFalse($this->handler->process($this->form, $this->request));
    }

    public function testProcessEmailSendFail()
    {
        $email = 'test@test.com';
        $token = 'answerisfourtytwo';

        $user = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\AccountUser')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->once())
            ->method('getConfirmationToken')
            ->will($this->returnValue($token));
        $user->expects($this->never())
            ->method('generateToken');
        $user->expects($this->never())
            ->method('setConfirmationToken');

        $this->assertValidFormCall($email);

        $this->userManager->expects($this->once())
            ->method('findUserByUsernameOrEmail')
            ->with($email)
            ->will($this->returnValue($user));

        $this->userManager->expects($this->once())
            ->method('sendResetPasswordEmail')
            ->with($user)
            ->will($this->throwException(new \Exception()));

        $this->assertFormErrorAdded(
            $this->form,
            'oro.email.handler.unable_to_send_email'
        );

        $this->assertFalse($this->handler->process($this->form, $this->request));
    }

    public function testProcess()
    {
        $email = 'test@test.com';
        $token = 'answerisfourtytwo';

        $user = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\AccountUser')
            ->disableOriginalConstructor()
            ->getMock();

        $user->expects($this->once())
            ->method('getConfirmationToken')
            ->will($this->returnValue(null));
        $user->expects($this->once())
            ->method('generateToken')
            ->will($this->returnValue($token));
        $user->expects($this->once())
            ->method('setConfirmationToken')
            ->with($token);
        $user->expects($this->once())
            ->method('setPasswordRequestedAt')
            ->with($this->isInstanceOf('\DateTime'));

        $this->assertValidFormCall($email);

        $this->userManager->expects($this->once())
            ->method('findUserByUsernameOrEmail')
            ->with($email)
            ->will($this->returnValue($user));

        $this->userManager->expects($this->once())
            ->method('sendResetPasswordEmail')
            ->with($user);

        $this->userManager->expects($this->once())
            ->method('updateUser')
            ->with($user);

        $this->assertEquals($user, $this->handler->process($this->form, $this->request));
    }

    /**
     * @param string $email
     */
    protected function assertValidFormCall($email)
    {
        parent::assertValidForm();

        $this->request->expects($this->once())
            ->method('isMethod')
            ->with('POST')
            ->will($this->returnValue(true));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $emailSubform = $this->getMock('Symfony\Component\Form\FormInterface');
        $emailSubform->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($email));

        $this->form->expects($this->once())
            ->method('get')
            ->with('email')
            ->will($this->returnValue($emailSubform));
    }
}
