<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Mailer;

use OroB2B\Bundle\CustomerBundle\Mailer\Processor;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

class ProcessorTest extends \PHPUnit_Framework_TestCase
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
        $this->user = new AccountUser();

        $this->mailProcessor = $this->getMockBuilder('OroB2B\Bundle\CustomerBundle\Mailer\Processor')
            ->disableOriginalConstructor()
            ->setMethods(['getEmailTemplateAndSendEmail'])
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->user, $this->mailProcessor);
    }

    public function testSendWelcomeNotification()
    {
        $this->mailProcessor->expects($this->once())
            ->method('getEmailTemplateAndSendEmail')
            ->with(
                $this->user,
                Processor::WELCOME_EMAIL_TEMPLATE_NAME,
                ['entity' => $this->user, 'password' => self::PASSWORD]
            )
            ->willReturn(true);

        $this->assertTrue($this->mailProcessor->sendWelcomeNotification($this->user, self::PASSWORD));
    }
}
