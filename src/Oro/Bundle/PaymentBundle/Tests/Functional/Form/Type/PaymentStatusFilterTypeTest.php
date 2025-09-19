<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Form\Type;

use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Form\Type\Filter\PaymentStatusFilterType;
use Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class PaymentStatusFilterTypeTest extends WebTestCase
{
    use FormAwareTestTrait;

    private TranslatorInterface $translator;

    private PaymentStatusLabelFormatter $paymentStatusLabelFormatter;

    protected function setUp(): void
    {
        $this->initClient();

        $this->translator = self::getContainer()->get('translator');
        $this->paymentStatusLabelFormatter = self::getContainer()->get('oro_payment.formatter.payment_status_label');
    }

    public function testFormCreation(): void
    {
        $form = self::createForm(PaymentStatusFilterType::class);

        self::assertFormHasField($form, 'type', ChoiceType::class);
        self::assertFormHasField($form, 'value', ChoiceType::class);
    }

    public function testGetBlockPrefix(): void
    {
        $form = self::createForm(PaymentStatusFilterType::class);

        self::assertEquals('oro_payment_status_filter', $form->getConfig()->getType()->getBlockPrefix());
    }

    public function testGetParent(): void
    {
        $form = self::createForm(PaymentStatusFilterType::class);

        self::assertEquals(
            ChoiceFilterType::class,
            get_class($form->getConfig()->getType()->getParent()->getInnerType())
        );
    }

    public function testFormOptions(): void
    {
        $form = self::createForm(PaymentStatusFilterType::class);

        $choices = [
            $this->translator->trans('oro.filter.form.label_type_contains') => ChoiceFilterType::TYPE_CONTAINS,
            $this->translator->trans('oro.filter.form.label_type_not_contains') => ChoiceFilterType::TYPE_NOT_CONTAINS,
        ];

        self::assertFormOptions($form, [
            'field_type' => ChoiceType::class,
            'field_options' => [
                'choices' => $this->paymentStatusLabelFormatter->getAvailableStatuses(),
                'translatable_options' => false,
            ],
            'operator_choices' => $choices,
            'populate_default' => false,
            'default_value' => null,
            'null_value' => null,
            'class' => null,
        ]);
    }

    public function testFormOptionsWithTargetEntity(): void
    {
        $targetEntity = Order::class;
        $form = self::createForm(PaymentStatusFilterType::class, null, [
            'target_entity' => $targetEntity,
        ]);

        self::assertFormOptions($form, [
            'target_entity' => $targetEntity,
        ]);

        self::assertFormHasField($form, 'value', ChoiceType::class, [
            'translatable_options' => false,
            'required' => false,
        ]);

        $valueField = $form->get('value');
        $choices = $valueField->getConfig()->getOption('choices');

        self::assertIsArray($choices);
        self::assertNotEmpty($choices);

        // Verify that the target entity affects available statuses
        $expectedChoices = $this->paymentStatusLabelFormatter->getAvailableStatuses($targetEntity);
        self::assertEquals($expectedChoices, $choices);
    }

    public function testFormOptionsWithRawLabels(): void
    {
        $form = self::createForm(PaymentStatusFilterType::class, null, [
            'raw_labels' => true,
        ]);

        self::assertFormOptions($form, [
            'raw_labels' => true,
        ]);

        self::assertFormHasField($form, 'value', ChoiceType::class, [
            'translatable_options' => false,
            'required' => false,
        ]);

        $valueField = $form->get('value');
        $choices = $valueField->getConfig()->getOption('choices');

        self::assertIsArray($choices);
        self::assertNotEmpty($choices);

        // With raw_labels and no target_entity, should get status codes for the entity.
        $availableStatuses = $this->paymentStatusLabelFormatter->getAvailableStatuses();
        $expectedChoices = array_values($availableStatuses);
        $expectedChoices = array_combine($expectedChoices, $expectedChoices);

        self::assertEquals($expectedChoices, $choices);
    }

    public function testFormOptionsWithExistingChoices(): void
    {
        $existingChoices = [
            'Custom Status 1' => 'custom_1',
            'Custom Status 2' => 'custom_2',
        ];

        $form = self::createForm(PaymentStatusFilterType::class, null, [
            'field_options' => [
                'choices' => $existingChoices,
            ],
        ]);

        self::assertFormHasField($form, 'value', ChoiceType::class, [
            'choices' => $existingChoices,
            'required' => false,
        ]);
    }

    public function testFormOptionsWithCustomFieldOptions(): void
    {
        $form = self::createForm(PaymentStatusFilterType::class, null, [
            'field_options' => [
                'multiple' => true,
                'expanded' => true,
                'attr' => ['class' => 'custom-class'],
            ],
        ]);

        self::assertFormHasField($form, 'value', ChoiceType::class, [
            'multiple' => true,
            'expanded' => true,
            'attr' => ['class' => 'custom-class'],
            'translatable_options' => false,
            'required' => false,
        ]);
    }

    public function testFormOptionsWithTargetEntityAndRawLabels(): void
    {
        $targetEntity = Order::class;
        $form = self::createForm(PaymentStatusFilterType::class, null, [
            'target_entity' => $targetEntity,
            'raw_labels' => true,
        ]);

        self::assertFormOptions($form, [
            'target_entity' => $targetEntity,
            'raw_labels' => true,
        ]);

        self::assertFormHasField($form, 'value', ChoiceType::class, [
            'translatable_options' => false,
            'required' => false,
        ]);

        $valueField = $form->get('value');
        $choices = $valueField->getConfig()->getOption('choices');

        self::assertIsArray($choices);
        self::assertNotEmpty($choices);

        // With raw_labels and target_entity, should get status codes for the entity
        $availableStatuses = $this->paymentStatusLabelFormatter->getAvailableStatuses($targetEntity);
        $expectedChoices = array_values($availableStatuses);
        $expectedChoices = array_combine($expectedChoices, $expectedChoices);

        self::assertEquals($expectedChoices, $choices);
    }

    public function testInheritedOperatorOptions(): void
    {
        $customOperatorOptions = [
            'attr' => ['data-operator' => 'test'],
            'placeholder' => 'Select operator',
        ];

        $form = self::createForm(PaymentStatusFilterType::class, null, [
            'operator_options' => $customOperatorOptions,
        ]);

        self::assertFormOptions($form, [
            'operator_options' => $customOperatorOptions,
        ]);

        self::assertFormHasField($form, 'type', ChoiceType::class, [
            'attr' => ['data-operator' => 'test'],
            'placeholder' => 'Select operator',
            'required' => false,
        ]);
    }

    public function testInheritedFilterTypeOptions(): void
    {
        $form = self::createForm(PaymentStatusFilterType::class, null, [
            'show_filter' => true,
            'lazy' => true,
        ]);

        self::assertFormOptions($form, [
            'show_filter' => true,
            'lazy' => true,
            'field_type' => ChoiceType::class,
        ]);
    }

    public function testFormSubmission(): void
    {
        $form = self::createForm(PaymentStatusFilterType::class);

        $form->submit([
            'type' => ChoiceFilterType::TYPE_CONTAINS,
            'value' => PaymentStatuses::PAID_IN_FULL,
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSubmitted());

        $data = $form->getData();
        self::assertEquals(ChoiceFilterType::TYPE_CONTAINS, $data['type']);
        self::assertEquals(PaymentStatuses::PAID_IN_FULL, $data['value']);
    }

    public function testFormSubmissionWithInvalidData(): void
    {
        $form = self::createForm(PaymentStatusFilterType::class);

        $form->submit([
            'type' => 'invalid_type',
            'value' => 'not_an_array',
        ]);

        // Form might still be submitted but data should be normalized
        self::assertTrue($form->isSubmitted());
        self::assertFalse($form->isValid());
    }

    public function testViewVarsWithDefaultOptions(): void
    {
        $form = self::createForm(PaymentStatusFilterType::class);
        $view = $form->createView();

        self::assertArrayHasKey('show_filter', $view->vars);
        self::assertFalse($view->vars['show_filter']);

        self::assertArrayHasKey('value', $view->vars);
        self::assertIsArray($view->vars['value']);
        self::assertArrayHasKey('type', $view->vars['value']);
        self::assertArrayHasKey('value', $view->vars['value']);

        // Check that populate_default is false by default
        self::assertArrayHasKey('populate_default', $view->vars);
        self::assertFalse($view->vars['populate_default']);

        self::assertArrayHasKey('default_value', $view->vars);
        self::assertNull($view->vars['default_value']);
    }

    public function testViewVarsWithShowFilter(): void
    {
        $form = self::createForm(PaymentStatusFilterType::class, null, [
            'show_filter' => true,
        ]);
        $view = $form->createView();

        self::assertArrayHasKey('show_filter', $view->vars);
        self::assertTrue($view->vars['show_filter']);
    }

    public function testViewVarsWithPopulateDefault(): void
    {
        $defaultValue = ['paid_in_full'];
        $form = self::createForm(PaymentStatusFilterType::class, null, [
            'populate_default' => true,
            'default_value' => $defaultValue,
        ]);
        $view = $form->createView();

        self::assertArrayHasKey('populate_default', $view->vars);
        self::assertTrue($view->vars['populate_default']);

        self::assertArrayHasKey('default_value', $view->vars);
        self::assertEquals($defaultValue, $view->vars['default_value']);
    }

    public function testViewVarsWithNullValue(): void
    {
        $nullValue = 'no_payment';
        $form = self::createForm(PaymentStatusFilterType::class, null, [
            'null_value' => $nullValue,
        ]);
        $view = $form->createView();

        self::assertArrayHasKey('null_value', $view->vars);
        self::assertEquals($nullValue, $view->vars['null_value']);
    }

    public function testViewVarsWithClass(): void
    {
        $cssClass = 'custom-payment-status-filter';
        $form = self::createForm(PaymentStatusFilterType::class, null, [
            'class' => $cssClass,
        ]);
        $view = $form->createView();

        self::assertArrayHasKey('class', $view->vars);
        self::assertEquals($cssClass, $view->vars['class']);
    }

    public function testValueFieldViewVarsWithoutRawLabels(): void
    {
        $form = self::createForm(PaymentStatusFilterType::class);
        $view = $form->createView();

        self::assertArrayHasKey('value', $view->children);
        $valueView = $view->children['value'];

        self::assertArrayHasKey('choices', $valueView->vars);
        self::assertNotEmpty($valueView->vars['choices']);

        $availableStatuses = $this->paymentStatusLabelFormatter->getAvailableStatuses();
        $expectedChoices = array_values($availableStatuses);
        $expectedChoices = array_combine($expectedChoices, $expectedChoices);

        foreach ($valueView->vars['choices'] as $choiceView) {
            self::assertInstanceOf(ChoiceView::class, $choiceView);
            self::assertContains($choiceView->value, $expectedChoices);
            self::assertEquals($expectedChoices[$choiceView->value], $choiceView->value);
            self::assertNotEquals($choiceView->label, $choiceView->value);
        }
    }

    public function testValueFieldViewVarsWithRawLabels(): void
    {
        $form = self::createForm(PaymentStatusFilterType::class, null, [
            'raw_labels' => true,
        ]);
        $view = $form->createView();

        self::assertArrayHasKey('value', $view->children);
        $valueView = $view->children['value'];

        self::assertArrayHasKey('choices', $valueView->vars);
        self::assertNotEmpty($valueView->vars['choices']);

        $availableStatuses = $this->paymentStatusLabelFormatter->getAvailableStatuses();
        $expectedChoices = array_values($availableStatuses);
        $expectedChoices = array_combine($expectedChoices, $expectedChoices);

        foreach ($valueView->vars['choices'] as $choiceView) {
            self::assertInstanceOf(ChoiceView::class, $choiceView);
            self::assertContains($choiceView->value, $expectedChoices);
            self::assertEquals($expectedChoices[$choiceView->value], $choiceView->value);
            self::assertEquals($choiceView->label, $choiceView->value);
        }
    }

    public function testTypeFieldViewVars(): void
    {
        $form = self::createForm(PaymentStatusFilterType::class);
        $view = $form->createView();

        self::assertArrayHasKey('type', $view->children);
        $typeView = $view->children['type'];

        self::assertArrayHasKey('choices', $typeView->vars);
        self::assertNotEmpty($typeView->vars['choices']);

        $expectedChoices = [
            $this->translator->trans('oro.filter.form.label_type_contains') => ChoiceFilterType::TYPE_CONTAINS,
            $this->translator->trans('oro.filter.form.label_type_not_contains') => ChoiceFilterType::TYPE_NOT_CONTAINS,
        ];

        foreach ($typeView->vars['choices'] as $choiceView) {
            self::assertInstanceOf(ChoiceView::class, $choiceView);
            self::assertContainsEquals($choiceView->value, $expectedChoices);
            self::assertEquals($expectedChoices[$choiceView->label], $choiceView->value);
        }
    }

    public function testViewVarsWithCustomOperatorOptions(): void
    {
        $customOperatorOptions = [
            'attr' => ['data-operator' => 'test'],
            'placeholder' => 'Select operator',
        ];

        $form = self::createForm(PaymentStatusFilterType::class, null, [
            'operator_options' => $customOperatorOptions,
        ]);
        $view = $form->createView();

        self::assertArrayHasKey('type', $view->children);
        $typeView = $view->children['type'];

        self::assertArrayHasKey('attr', $typeView->vars);
        self::assertEquals([
            'data-operator' => 'test',
            'data-ftid' => 'oro_payment_status_filter_type',
            'data-name' => 'field__type',
        ], $typeView->vars['attr']);

        self::assertArrayHasKey('placeholder', $typeView->vars);
        self::assertEquals('Select operator', $typeView->vars['placeholder']);
    }

    public function testViewVarsWithFormData(): void
    {
        $form = self::createForm(PaymentStatusFilterType::class);

        $formData = [
            'type' => (string)ChoiceFilterType::TYPE_CONTAINS,
            'value' => PaymentStatuses::PAID_IN_FULL,
        ];

        $form->submit($formData);
        $view = $form->createView();

        self::assertArrayHasKey('value', $view->vars);
        self::assertEquals($formData, $view->vars['value']);

        // Check child views have correct data
        self::assertEquals(ChoiceFilterType::TYPE_CONTAINS, $view->children['type']->vars['value']);
        self::assertEquals(PaymentStatuses::PAID_IN_FULL, $view->children['value']->vars['value']);
    }
}
