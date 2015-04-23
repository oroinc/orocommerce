<?php

namespace OroB2B\Bundle\EmailBundle\Tests\Unit\Mailer;

use OroB2B\Bundle\EmailBundle\Mailer\Mailer;

class MailerTest extends \PHPUnit_Framework_TestCase
{
    const EMAIL_CONTENT = 'Test content';
    const EMAIL_SUBJECT = 'Test subject';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Swift_Mailer
     */
    protected $swiftMailer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Oro\Bundle\ApplicationBundle\Config\ConfigManager
     */
    protected $configManager;

    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->swiftMailer = $this->getMockBuilder('Swift_Mailer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMock('Oro\Bundle\ApplicationBundle\Config\ConfigManager');

        $this->mailer = new Mailer($this->swiftMailer, $this->configManager);
    }

    public function testSend()
    {
        $message = \Swift_Message::newInstance();

        $this->swiftMailer->expects($this->once())
            ->method('createMessage')
            ->willReturn($message);
        $this->swiftMailer->expects($this->once())
            ->method('send')
            ->with($message)
            ->willReturn(1);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_b2b_rfp_admin.default_user_for_notifications')
            ->willReturn('from@example.com');

        $result = $this->mailer->send(self::EMAIL_SUBJECT, self::EMAIL_CONTENT, 'to@example.com');
        $this->assertEquals(1, $result);
    }

    public function testSendIfSystemConfigurationParameterNotSet()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_b2b_rfp_admin.default_user_for_notifications')
            ->willReturn(null);

        $result = $this->mailer->send(self::EMAIL_SUBJECT, self::EMAIL_CONTENT, 'to@example.com');
        $this->assertEquals(0, $result);
    }
}
