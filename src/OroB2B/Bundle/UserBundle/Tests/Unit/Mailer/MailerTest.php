<?php

namespace OroB2B\Bundle\UserBundle\Tests\Unit\Mailer;

use OroB2B\Bundle\UserBundle\Mailer\Mailer;

class MailerTest extends \PHPUnit_Framework_TestCase
{
    public function testSendEmailMessage()
    {
        $emailText = 'Email Test Text';
        $emailFrom = 'from@example.com';
        $nameFrom = 'John Dow';
        $emailTo = 'to@example.com';

        $swiftMailer = $this->getMockBuilder('Swift_Mailer')
            ->disableOriginalConstructor()
            ->getMock();
        $swiftMailer->expects($this->once())
            ->method('send');

        $router = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $templating = $this->getMock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface');

        $configManager = $this->getMock('Oro\Bundle\ApplicationBundle\Config\ConfigManager');
        $configManager->expects($this->at(0))
            ->method('get')
            ->with('oro_notification.email_notification_sender_email')
            ->willReturn($emailFrom);
        $configManager->expects($this->at(1))
            ->method('get')
            ->with('oro_notification.email_notification_sender_name')
            ->willReturn($nameFrom);

        $mailer = new Mailer($swiftMailer, $router, $templating, [], $configManager);

        $class = new \ReflectionClass($mailer);
        $method = $class->getMethod('sendEmailMessage');
        $method->setAccessible(true);

        $method->invokeArgs($mailer, [$emailText, 'admin@example.com', $emailTo]);
    }
}
