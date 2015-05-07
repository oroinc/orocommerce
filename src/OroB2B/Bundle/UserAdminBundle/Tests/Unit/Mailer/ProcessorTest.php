<?php

namespace OroB2B\Bundle\UserAdminBundle\Tests\Unit\Mailer;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;

use OroB2B\Bundle\UserAdminBundle\Mailer\Processor;
use OroB2B\Bundle\UserAdminBundle\Entity\User;

class ProcessorTest extends \PHPUnit_Framework_TestCase
{
    const SUBJECT = 'Subject';
    const BODY = 'Test body';
    const PASSWORD = '123456';

    /**
     * @var EmailTemplateRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectRepository;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * @var EmailRenderer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $renderer;

    /**
     * @var \Swift_Mailer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mailer;

    /**
     * @var Processor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mailProcessor;

    /**
     * @var EmailTemplateInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailTemplate;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var array
     */
    protected $templateData;

    protected function setUp()
    {
        $this->user = new User();
        $this->templateData = [self::SUBJECT, self::BODY];

        $this->objectManager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectRepository = $this->getMockBuilder(
            'Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->objectRepository);

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->renderer = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailRenderer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->renderer->expects($this->any())
            ->method('compileMessage')
            ->willReturn($this->templateData);

        $this->mailer = $this->getMockBuilder('\Swift_Mailer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mailProcessor = new Processor(
            $this->objectManager,
            $this->configManager,
            $this->renderer,
            $this->mailer
        );

        $this->emailTemplate = $this->getMock('Oro\Bundle\EmailBundle\Model\EmailTemplateInterface');
    }

    /**
     * Test Welcome email notification
     */
    public function testSendWelcomeNotification()
    {
        $this->objectRepository->expects($this->once())
            ->method('findByName')
            ->with(Processor::WELCOME_EMAIL_TEMPLATE_NAME)
            ->willReturn($this->emailTemplate);

        $this->emailTemplate->expects($this->once())
            ->method('getType')
            ->willReturn('txt');

        $this->configManager->expects($this->at(0))
            ->method('get')
            ->with('oro_notification.email_notification_sender_email')
            ->will($this->returnValue('sender@example.com'));

        $this->configManager->expects($this->at(1))
            ->method('get')
            ->with('oro_notification.email_notification_sender_name')
            ->will($this->returnValue('test'));

        $this->mailer->expects($this->any())
            ->method('send')
            ->willReturnCallback(function ($message) {
                $this->assertEquals(self::SUBJECT, $message->getSubject());
                $this->assertEquals(self::BODY, $message->getBody());
            });

        $this->mailProcessor->sendWelcomeNotification($this->user, self::PASSWORD);
    }
}
