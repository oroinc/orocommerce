<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Mailer;

use Oro\Bundle\UserBundle\Tests\Unit\Mailer\AbstractProcessorTest;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Mailer\AccountUserProcessor;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class AccountUserProcessorTest extends AbstractProcessorTest
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
        $accountUser = new AccountUser();
        $accountUser->setWebsite($this->website);

        $templateName = 'email_template_name';
        $templateParams = ['entity' => $accountUser];
        $expectedMessage = $this->buildMessage(
            $accountUser->getEmail(),
            'subject',
            'body',
            'text/html'
        );

        $this->assertSendCalled($templateName, $templateParams, $expectedMessage, 'html');

        $this->mailProcessor->getEmailTemplateAndSendEmail($accountUser, $templateName, $templateParams);
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
     * @return AccountUserProcessor
     */
    protected function setProcessor()
    {
        return new AccountUserProcessor(
            $this->managerRegistry,
            $this->configManager,
            $this->renderer,
            $this->emailHolderHelper,
            $this->mailer
        );
    }
}
