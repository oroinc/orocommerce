<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Form\Extension;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Type\EmailType;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormFactoryInterface;

class QuoteEmailTemplateExtensionTest extends WebTestCase
{
    use DefaultWebsiteIdTestTrait;
    use ConfigManagerAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);

        $this->loadFixtures([
            '@OroSaleBundle/Tests/Functional/Form/Extension/DataFixtures/QuoteEmailTemplateExtension.yml',
        ]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_sale.enable_guest_quote', false);
        $configManager->flush();
    }

    public function testBuildWhenNoData(): void
    {
        $formFactory = self::getContainer()->get(FormFactoryInterface::class);

        $form = $formFactory->create(EmailType::class, null, ['csrf_protection' => false]);

        self::assertTrue($form->has('template'));
        self::assertEquals('name', $form->get('template')->getConfig()->getOption('choice_label'));

        $formView = $form->createView();

        self::assertEmpty(
            array_map(static fn (ChoiceView $choiceView) => $choiceView->label, $formView['template']->vars['choices'])
        );
    }

    public function testBuildWhenNotQuote(): void
    {
        $formFactory = self::getContainer()->get(FormFactoryInterface::class);

        $form = $formFactory->create(
            EmailType::class,
            (new EmailModel())->setEntityClass(Order::class),
            ['csrf_protection' => false]
        );

        self::assertTrue($form->has('template'));
        self::assertEquals('name', $form->get('template')->getConfig()->getOption('choice_label'));

        $formView = $form->createView();

        self::assertContains(
            'another_email_template',
            array_map(static fn (ChoiceView $choiceView) => $choiceView->label, $formView['template']->vars['choices'])
        );
    }

    public function testBuildWhenQuote(): void
    {
        $formFactory = self::getContainer()->get(FormFactoryInterface::class);

        $form = $formFactory->create(
            EmailType::class,
            (new EmailModel())->setEntityClass(Quote::class),
            ['csrf_protection' => false]
        );

        self::assertTrue($form->has('template'));
        self::assertEquals(Quote::class, $form->get('template')->getConfig()->getOption('selectedEntity'));

        $formView = $form->createView();

        self::assertEqualsCanonicalizing(
            ['quote_email_link (Default)', 'quote_email_link'],
            array_map(static fn (ChoiceView $choiceView) => $choiceView->label, $formView['template']->vars['choices'])
        );
    }

    public function testBuildWhenQuoteAndGuestAccess(): void
    {
        $formFactory = self::getContainer()->get(FormFactoryInterface::class);
        $configManager = self::getConfigManager();
        $configManager->set('oro_sale.enable_guest_quote', true);
        $configManager->flush();

        $form = $formFactory->create(
            EmailType::class,
            (new EmailModel())->setEntityClass(Quote::class),
            ['csrf_protection' => false]
        );

        self::assertTrue($form->has('template'));
        self::assertEquals(Quote::class, $form->get('template')->getConfig()->getOption('selectedEntity'));

        $formView = $form->createView();

        self::assertEqualsCanonicalizing(
            ['quote_email_link (Default)', 'quote_email_link', 'quote_email_link_guest'],
            array_map(static fn (ChoiceView $choiceView) => $choiceView->label, $formView['template']->vars['choices'])
        );
    }
}
