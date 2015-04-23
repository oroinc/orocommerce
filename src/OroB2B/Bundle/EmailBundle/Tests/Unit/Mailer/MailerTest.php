<?php

namespace OroB2B\Bundle\EmailBundle\Tests\Unit\Mailer;

use OroB2B\Bundle\EmailBundle\Entity\EmailTemplate;
use OroB2B\Bundle\EmailBundle\Mailer\Mailer;

class MailerTest extends \PHPUnit_Framework_TestCase
{
    const EMAIL_CONTENT = 'Test content';
    const EMAIL_SUBJECT = 'Test subject';
    const ENTITY_CLASS = '\stdClass';

    /**
     * @var EmailTemplate
     */
    protected $template;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $swiftMailer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $twig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
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
        $this->template = new EmailTemplate();
        $this->template
            ->setContent(self::EMAIL_CONTENT)
            ->setEntityName(self::ENTITY_CLASS)
            ->setSubject(self::EMAIL_SUBJECT);

        $this->swiftMailer = $this->getMockBuilder('Swift_Mailer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->twig = $this->getMock('Twig_Environment');

        $this->configManager = $this->getMock('Oro\Bundle\ApplicationBundle\Config\ConfigManager');

        $this->mailer = new Mailer($this->swiftMailer, $this->twig, $this->configManager);
    }

    /**
     * Test send
     */
    public function testSend()
    {
        $entity = self::ENTITY_CLASS;
        $entity = new $entity();

        $message = \Swift_Message::newInstance();

        $this->swiftMailer->expects($this->once())
            ->method('createMessage')
            ->willReturn($message);
        $this->swiftMailer->expects($this->once())
            ->method('send');

        $this->twig->expects($this->at(0))
            ->method('render')
            ->with(self::EMAIL_CONTENT, ['entity' => $entity])
            ->willReturn(self::EMAIL_CONTENT);
        $this->twig->expects($this->at(1))
            ->method('render')
            ->with(self::EMAIL_SUBJECT, ['entity' => $entity])
            ->willReturn(self::EMAIL_SUBJECT);

        $this->configManager->expects($this->at(0))
            ->method('get')
            ->with('oro_notification.email_notification_sender_email')
            ->willReturn('from@example.com');
        $this->configManager->expects($this->at(1))
            ->method('get')
            ->with('oro_notification.email_notification_sender_name')
            ->willReturn('John Dow');

        $this->mailer->send($this->template, $entity, 'to@example.com');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Entity variable should be instance of \stdClass class
     */
    public function testAssertEntityVariableWithAnotherObject()
    {
        $entity = $this->template;

        $this->mailer->send($this->template, $entity, 'to@example.com');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Entity variable should be an object
     */
    public function testAssertEntityVariableWithNoObjectVariable()
    {
        $entity = 'string';

        $this->mailer->send($this->template, $entity, 'to@example.com');
    }
}
