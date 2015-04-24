<?php

namespace OroB2B\Bundle\EmailBundle\Tests\Unit\Mailer;

use OroB2B\Bundle\EmailBundle\Entity\EmailTemplate;
use OroB2B\Bundle\EmailBundle\Mailer\EmailSendProcessor;
use OroB2B\Bundle\RFPBundle\Entity\Request;

class EmailSendProcessorTest extends \PHPUnit_Framework_TestCase
{
    const EMAIL_CONTENT = 'Test content';
    const EMAIL_SUBJECT = 'Test subject';
    const ENTITY_CLASS = 'OroB2B\Bundle\RFPBundle\Entity\Request';

    /**
     * @var Request
     */
    protected $entity;

    /**
     * @var EmailTemplate
     */
    protected $template;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Doctrine\Common\Persistence\ObjectRepository
     */
    protected $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Doctrine\Common\Persistence\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment
     */
    protected $twig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Oro\Bundle\ApplicationBundle\Config\ConfigManager
     */
    protected $configManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\OroB2B\Bundle\EmailBundle\Mailer\Mailer
     */
    protected $mailer;

    /**
     * @var EmailSendProcessor
     */
    protected $processor;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->entity = self::ENTITY_CLASS;
        $this->entity = new $this->entity();

        $this->template = new EmailTemplate();
        $this->template
            ->setContent(self::EMAIL_CONTENT)
            ->setEntityName(self::ENTITY_CLASS)
            ->setSubject(self::EMAIL_SUBJECT);

        $this->repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');

        $this->objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroB2BEmailBundle:EmailTemplate')
            ->willReturn($this->objectManager);

        $this->twig = $this->getMock('Twig_Environment');

        $this->configManager = $this->getMock('Oro\Bundle\ApplicationBundle\Config\ConfigManager', ['get']);

        $this->mailer = $this->getMockBuilder('OroB2B\Bundle\EmailBundle\Mailer\Mailer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new EmailSendProcessor($managerRegistry, $this->twig, $this->configManager, $this->mailer);
    }

    /**
     * Test send
     */
    public function testSend()
    {
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => EmailSendProcessor::CREATE_REQUEST_TEMPLATE_NAME])
            ->willReturn($this->template);

        $this->objectManager->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BEmailBundle:EmailTemplate')
            ->willReturn($this->repository);

        $this->twig->expects($this->at(0))
            ->method('render')
            ->with(self::EMAIL_SUBJECT, ['entity' => $this->entity])
            ->willReturn(self::EMAIL_SUBJECT);
        $this->twig->expects($this->at(1))
            ->method('render')
            ->with(self::EMAIL_CONTENT, ['entity' => $this->entity])
            ->willReturn(self::EMAIL_CONTENT);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_b2b_rfp_admin.default_user_for_notifications')
            ->willReturn('from@example.com');

        $this->processor->sendRequestCreateNotification($this->entity);
    }

    public function testSendApplicationConfigurationParameter()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_b2b_rfp_admin.default_user_for_notifications')
            ->willReturn(null);

        $this->mailer->expects($this->never())
            ->method('send');

        $this->processor->sendRequestCreateNotification($this->entity);
    }

    /**
     * @expectedException \OroB2B\Bundle\EmailBundle\Exception\EmailTemplateNotFoundException
     * @expectedExceptionMessage Couldn't find email template with name request_create_notification
     */
    public function testSendWithoutEmailTemplate()
    {
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => EmailSendProcessor::CREATE_REQUEST_TEMPLATE_NAME])
            ->willReturn(null);

        $this->objectManager->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BEmailBundle:EmailTemplate')
            ->willReturn($this->repository);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_b2b_rfp_admin.default_user_for_notifications')
            ->willReturn('from@example.com');

        $this->processor->sendRequestCreateNotification($this->entity);
    }
}
