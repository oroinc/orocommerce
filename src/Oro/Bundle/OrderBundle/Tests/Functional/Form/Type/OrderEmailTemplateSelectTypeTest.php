<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\Form\Type;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\OrderEmailTemplateSelectType;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;

class OrderEmailTemplateSelectTypeTest extends WebTestCase
{
    use FormAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);
    }

    public function testConfigureOptions(): void
    {
        $form = self::createForm(OrderEmailTemplateSelectType::class);

        self::assertFormOptions($form, [
            'class' => EmailTemplate::class,
            'choice_label' => 'name',
            'choice_value' => 'name',
        ]);

        self::assertNotNull($form->getConfig()->getOption('query_builder'));
    }

    public function testGetBlockPrefix(): void
    {
        $form = self::createForm(OrderEmailTemplateSelectType::class);
        $formView = $form->createView();

        self::assertContains('oro_order_order_email_template_select', $formView->vars['block_prefixes']);
    }

    public function testGetParent(): void
    {
        $form = self::createForm(OrderEmailTemplateSelectType::class);

        self::assertEquals(
            Select2TranslatableEntityType::class,
            \get_class($form->getConfig()->getType()->getParent()->getInnerType())
        );
    }

    public function testSubmitWithNull(): void
    {
        $form = self::createForm(OrderEmailTemplateSelectType::class);
        $form->submit(null);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEmpty($form->getData());
    }

    public function testSubmitWithEmptyString(): void
    {
        $form = self::createForm(OrderEmailTemplateSelectType::class);
        $form->submit('');

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEmpty($form->getData());
    }

    public function testSubmitWithValidTemplateName(): void
    {
        $form = self::createForm(OrderEmailTemplateSelectType::class);
        $form->submit('order_confirmation_email');

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());

        $data = $form->getData();
        self::assertInstanceOf(EmailTemplate::class, $data);
        self::assertEquals('order_confirmation_email', $data->getName());
    }

    public function testSubmitWithNonExistingTemplateName(): void
    {
        $form = self::createForm(OrderEmailTemplateSelectType::class);
        $form->submit('non_existing_template');

        self::assertFalse($form->isSynchronized());
        self::assertFalse($form->isValid());
        self::assertNull($form->getData());

        $errors = $form->getErrors();
        self::assertCount(1, $errors);
        self::assertEquals('The selected choice is invalid.', $errors[0]->getMessage());
    }

    public function testChoicesContainOnlyOrderTemplates(): void
    {
        $form = self::createForm(OrderEmailTemplateSelectType::class);
        $formView = $form->createView();

        $choices = $formView->vars['choices'];

        self::assertNotEmpty($choices, 'Should have at least order_confirmation_email template');
        $templateNames = \array_map(static fn ($choice) => $choice->label, $choices);
        self::assertContains('order_confirmation_email', $templateNames);

        /** @var EmailTemplateRepository $emailTemplateRepository */
        $emailTemplateRepository = self::getContainer()->get('doctrine')->getRepository(EmailTemplate::class);

        // Verify all choices are for Order entity
        foreach ($choices as $choice) {
            if ($choice->value) {
                $template = $emailTemplateRepository->findByName($choice->value);
                if ($template) {
                    self::assertEquals(
                        Order::class,
                        $template->getEntityName(),
                        \sprintf('Template "%s" should be for Order entity', $choice->value)
                    );
                }
            }
        }
    }

    public function testModelTransformerConvertsTemplateToName(): void
    {
        $template = self::getContainer()
            ->get('doctrine')
            ->getRepository(EmailTemplate::class)
            ->findByName('order_confirmation_email');

        self::assertNotNull($template, 'order_confirmation_email template should exist');

        $form = self::createForm(OrderEmailTemplateSelectType::class, $template);
        $formView = $form->createView();

        self::assertEquals('order_confirmation_email', $formView->vars['value']);
    }

    public function testModelTransformerHandlesNullTemplate(): void
    {
        $form = self::createForm(OrderEmailTemplateSelectType::class, null);
        $formView = $form->createView();

        self::assertEquals('', $formView->vars['value']);
    }
}
