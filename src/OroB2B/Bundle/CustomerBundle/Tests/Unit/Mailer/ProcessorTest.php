<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Mailer;

use Oro\Bundle\UserBundle\Tests\Unit\Mailer\AbstractProcessorTest;

use OroB2B\Bundle\CustomerBundle\Mailer\Processor;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

class ProcessorTest extends AbstractProcessorTest
{
    const PASSWORD = '123456';

    /**
     * @var Processor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mailProcessor;

    /**
     * @var AccountUser
     */
    protected $user;

    protected function setUp()
    {
        parent::setUp();

        $this->user = new AccountUser();
        $this->user
            ->setEmail('email_to@example.com')
            ->setPlainPassword(self::PASSWORD);

        $this->mailProcessor = new Processor(
            $this->managerRegistry,
            $this->configManager,
            $this->renderer,
            $this->emailHolderHelper,
            $this->mailer
        );
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->user);
    }

    public function testSendWelcomeNotification()
    {
        $this->assertSendCalled(
            Processor::WELCOME_EMAIL_TEMPLATE_NAME,
            ['entity' => $this->user, 'password' => self::PASSWORD],
            $this->buildMessage($this->user->getEmail())
        );

        $this->mailProcessor->sendWelcomeNotification($this->user, self::PASSWORD);
    }

    public function testSendResetPasswordEmail()
    {
        $this->assertSendCalled(
            Processor::RESET_PASSWORD_EMAIL_TEMPLATE_NAME,
            ['entity' => $this->user],
            $this->buildMessage($this->user->getEmail())
        );

        $this->mailProcessor->sendResetPasswordEmail($this->user);
    }
}
