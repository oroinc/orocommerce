<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Mailer;

use Oro\Bundle\UserBundle\Tests\Unit\Mailer\AbstractProcessorTest;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Mailer\CustomerUserProcessor;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CustomerUserProcessorTest extends AbstractProcessorTest
{
    use EntityTrait;

    /**
     * @var Website
     */
    protected $website;

    protected function setUp()
    {
        $this->website = $this->getEntity(Website::class, ['id' => 123]);
        parent::setUp();
    }

    public function testSendEmail()
    {
        $customerUser = new CustomerUser();
        $customerUser->setWebsite($this->website);

        $templateName = 'email_template_name';
        $templateParams = ['entity' => $customerUser];
        $expectedMessage = $this->buildMessage(
            $customerUser->getEmail(),
            'subject',
            'body',
            'text/html'
        );

        $this->assertSendCalled($templateName, $templateParams, $expectedMessage, 'html');

        $this->mailProcessor->getEmailTemplateAndSendEmail($customerUser, $templateName, $templateParams);
    }

    protected function setConfigManager()
    {
        $this->configManager = $this->getMockForClass('Oro\Bundle\ConfigBundle\Config\ConfigManager');
        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    [
                        'oro_notification.email_notification_sender_email',
                        false,
                        false,
                        $this->website,
                        self::FROM_EMAIL
                    ],
                    [
                        'oro_notification.email_notification_sender_name',
                        false,
                        false,
                        $this->website,
                        self::FROM_NAME
                    ]
                ]
            );
    }

    /**
     * @return CustomerUserProcessor
     */
    protected function setProcessor()
    {
        return new CustomerUserProcessor(
            $this->managerRegistry,
            $this->configManager,
            $this->renderer,
            $this->emailHolderHelper,
            $this->mailer
        );
    }
}
