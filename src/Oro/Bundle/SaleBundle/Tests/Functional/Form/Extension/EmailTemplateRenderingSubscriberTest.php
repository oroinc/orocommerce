<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Tests\Functional\Form\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUser;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate as EmailTemplateEntity;
use Oro\Bundle\EmailBundle\Form\Type\EmailType;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Checks that an email model created by quote contains compiled and localized email template content.
 */
class EmailTemplateRenderingSubscriberTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ?array $initialEnabledLocalizations;
    private FormFactoryInterface $formFactory;
    private ManagerRegistry $doctrine;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadUserData::class,
            '@OroSaleBundle/Tests/Functional/Form/Extension/DataFixtures/EmailTemplateRenderingSubscriber.yml',
            '@OroSaleBundle/Tests/Functional/Form/Extension/DataFixtures/EmailTemplateRenderingSubscriber.quote.yml',
        ]);

        $configManager = self::getConfigManager();
        $this->initialEnabledLocalizations = $configManager->get('oro_locale.enabled_localizations');
        $configManager->set(
            'oro_locale.enabled_localizations',
            array_merge($this->initialEnabledLocalizations, [$this->getReference('localization_de')->getId()])
        );
        $configManager->flush();

        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);

        $this->formFactory = self::getContainer()->get(FormFactoryInterface::class);

        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference(LoadCustomerUser::CUSTOMER_USER);
        $customerUser->setWebsite($this->getReference('website'));

        $this->doctrine = self::getContainer()->get('doctrine');
        $this->doctrine->getManagerForClass(CustomerUser::class)->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $quote = $this->getReference('quote_1');
        $this->switchCustomerUserLocalization($quote->getCustomerUser(), null);

        $configManager = self::getConfigManager();
        $configManager->set('oro_locale.enabled_localizations', $this->initialEnabledLocalizations);
        $configManager->flush();
    }

    public function testRegularEmailTemplateIsCompiled(): void
    {
        /** @var Quote $quote */
        $quote = $this->getReference('quote_1');
        /** @var EmailTemplateEntity $emailTemplateEntity */
        $emailTemplateEntity = $this->getReference('email_template_regular');
        $emailModel = self::getContainer()->get('oro_sale.helper.notification')
            ->getEmailModel($quote)
            ->setTemplate($emailTemplateEntity);

        $this->formFactory->create(EmailType::class, $emailModel);

        self::assertEquals('Email Template Regular', $emailModel->getSubject());
        self::assertStringContainsString('Email Template Regular Content', $emailModel->getBody());
    }

    public function testRegularEmailTemplateIsCompiledInDifferentLocalization(): void
    {
        /** @var Quote $quote */
        $quote = $this->getReference('quote_1');
        /** @var Localization $localizationDe */
        $localizationDe = $this->getReference('localization_de');

        $this->switchCustomerUserLocalization($quote->getCustomerUser(), $localizationDe);

        /** @var EmailTemplateEntity $emailTemplateEntity */
        $emailTemplateEntity = $this->getReference('email_template_regular');
        $emailModel = self::getContainer()->get('oro_sale.helper.notification')
            ->getEmailModel($quote)
            ->setTemplate($emailTemplateEntity);

        $this->formFactory->create(EmailType::class, $emailModel);

        self::assertEquals('Email Template (DE) Regular', $emailModel->getSubject());
        self::assertStringContainsString('Email Template (DE) Regular Content', $emailModel->getBody());
    }

    public function testExtendedEmailTemplateIsCompiled(): void
    {
        $quote = $this->getReference('quote_1');
        $template = $this->getReference('email_template_extended');
        $emailModel = self::getContainer()->get('oro_sale.helper.notification')
            ->getEmailModel($quote)
            ->setTemplate($template);

        $this->formFactory->create(EmailType::class, $emailModel);

        self::assertEquals('Email Template Extended', $emailModel->getSubject());
        self::assertStringContainsString('Email Template Base Content', $emailModel->getBody());
        self::assertStringContainsString('Email Template Extended Content', $emailModel->getBody());
    }

    public function testExtendedEmailTemplateIsCompiledInDifferentLocalization(): void
    {
        /** @var Quote $quote */
        $quote = $this->getReference('quote_1');
        /** @var Localization $localizationDe */
        $localizationDe = $this->getReference('localization_de');

        $this->switchCustomerUserLocalization($quote->getCustomerUser(), $localizationDe);

        /** @var EmailTemplateEntity $emailTemplateEntity */
        $emailTemplateEntity = $this->getReference('email_template_extended');
        $emailModel = self::getContainer()->get('oro_sale.helper.notification')
            ->getEmailModel($quote)
            ->setTemplate($emailTemplateEntity);

        $this->formFactory->create(EmailType::class, $emailModel);

        self::assertEquals('Email Template (DE) Extended', $emailModel->getSubject());
        self::assertStringContainsString('Email Template (DE) Base', $emailModel->getBody());
        self::assertStringContainsString('Email Template (DE) Extended Content', $emailModel->getBody());
    }

    private function switchCustomerUserLocalization(CustomerUser $customerUser, ?Localization $localization): void
    {
        $website = $customerUser->getWebsite();
        $userWebsiteSettings = $customerUser->getWebsiteSettings($website);
        if (!$userWebsiteSettings) {
            $userWebsiteSettings = new CustomerUserSettings($website);
            $customerUser->setWebsiteSettings($userWebsiteSettings);
        }

        $userWebsiteSettings->setLocalization($localization);
        $this->doctrine->getManagerForClass(CustomerUser::class)->flush();
    }
}
