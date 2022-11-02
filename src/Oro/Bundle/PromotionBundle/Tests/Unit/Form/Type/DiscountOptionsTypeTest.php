<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\MultiCurrencyType;
use Oro\Bundle\FormBundle\Form\Type\OroMoneyType;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Form\Type\DiscountOptionsType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DiscountOptionsTypeTest extends FormIntegrationTestCase
{
    public function testGetBlockPrefix()
    {
        $formType = new DiscountOptionsType();
        $this->assertEquals(DiscountOptionsType::NAME, $formType->getBlockPrefix());
    }

    public function testInitialForm()
    {
        $form = $this->factory->create(DiscountOptionsType::class);

        $this->assertFormFieldsPreset($form);
        $this->assertFieldIsHidden($form, DiscountOptionsType::PERCENT_DISCOUNT_VALUE_FIELD);
    }

    public function testFormWithTypeAmountSelected()
    {
        $form = $this->factory->create(
            DiscountOptionsType::class,
            [AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_AMOUNT]
        );

        $this->assertFormFieldsPreset($form);
        $this->assertFieldIsHidden($form, DiscountOptionsType::PERCENT_DISCOUNT_VALUE_FIELD);
    }

    public function testFormWithTypePercentSelected()
    {
        $form = $this->factory->create(
            DiscountOptionsType::class,
            [AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_PERCENT]
        );

        $this->assertFormFieldsPreset($form);
        $this->assertFieldIsHidden($form, DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $submittedData, array $expectedData)
    {
        $form = $this->factory->create(DiscountOptionsType::class, []);
        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        return [
            'options for percent discount' => [
                'submittedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_PERCENT,
                    DiscountOptionsType::PERCENT_DISCOUNT_VALUE_FIELD => 123
                ],
                'expectedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_PERCENT,
                    AbstractDiscount::DISCOUNT_VALUE => 1.23
                ]
            ],
            'options for amount discount' => [
                'submittedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_AMOUNT,
                    DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD => ['value' => '123.0000', 'currency' => 'USD'],
                ],
                'expectedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 123,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                ]
            ]
        ];
    }

    /**
     * @dataProvider submitInvalidDataProvider
     */
    public function testSubmitInvalid(array $submittedData)
    {
        $form = $this->factory->create(DiscountOptionsType::class, []);
        $form->submit($submittedData);
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->isSynchronized());
    }

    public function submitInvalidDataProvider(): array
    {
        return [
            'invalid type percent' => [
                'submittedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_PERCENT,
                    DiscountOptionsType::PERCENT_DISCOUNT_VALUE_FIELD => 'abc'
                ]
            ],
            'null percent' => [
                'submittedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_PERCENT,
                    DiscountOptionsType::PERCENT_DISCOUNT_VALUE_FIELD => null
                ]
            ],
            'invalid type amount value' => [
                'submittedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_AMOUNT,
                    DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD => ['value' => '123$', 'currency' => 'USD'],
                ]
            ],
            'null amount' => [
                'submittedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_AMOUNT,
                    DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD => ['value' => null, 'currency' => 'USD'],
                ]
            ],
            'wrong percent value' => [
                'submittedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_PERCENT,
                    DiscountOptionsType::PERCENT_DISCOUNT_VALUE_FIELD => 1000000,
                ]
            ],
        ];
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnCallback(
                function (array $options) {
                    $this->assertArrayHasKey('type_choices', $options);
                    $this->assertArrayHasKey('page_component', $options);
                    $this->assertArrayHasKey('page_component_options', $options);
                    $this->assertEquals(
                        [
                            'oro.discount_options.general.type.choices.amount' => 'amount',
                            'oro.discount_options.general.type.choices.percent' => 'percent',
                        ],
                        $options['type_choices']
                    );
                    $this->assertEquals('oroui/js/app/components/view-component', $options['page_component']);
                    $this->assertEquals(
                        [
                            'view' => 'oropromotion/js/app/views/type-value-switcher',
                            'amount_type_value' => 'amount',
                            'percent_type_value' => 'percent',
                            'type_selector' => '[name*="[discount_type]"]',
                            'amount_discount_value_selector' => '[name*="[amount_discount_value]"]',
                            'percent_discount_value_selector' => '[name*="[percent_discount_value]"]'
                        ],
                        $options['page_component_options']
                    );
                }
            );

        $formType = new DiscountOptionsType();
        $formType->configureOptions($resolver);
    }

    public function testFinishView()
    {
        $form = $this->factory->create(DiscountOptionsType::class);
        $view = $form->createView();
        $this->assertArrayHasKey('attr', $view->vars);
        $attr = $view->vars['attr'];
        $this->assertSame($form->getConfig()->getOption('page_component'), $attr['data-page-component-module']);
        $this->assertSame(
            json_encode($form->getConfig()->getOption('page_component_options'), JSON_THROW_ON_ERROR),
            $attr['data-page-component-options']
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        $localeSettings = $this->createMock(LocaleSettings::class);
        $numberFormatter = $this->createMock(NumberFormatter::class);

        return [
            new PreloadedExtension(
                [
                    new MultiCurrencyType(),
                    new OroMoneyType($localeSettings, $numberFormatter),
                    CurrencySelectionType::class => new CurrencySelectionTypeStub()
                ],
                [
                    FormType::class => [new TooltipFormExtensionStub($this)]
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    private function assertFormFieldsPreset(FormInterface $form)
    {
        $this->assertTrue($form->has(AbstractDiscount::DISCOUNT_TYPE));
        $this->assertTrue($form->has(DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD));
        $this->assertTrue($form->has(DiscountOptionsType::PERCENT_DISCOUNT_VALUE_FIELD));
    }

    private function assertFieldIsHidden(FormInterface $form, string $field): void
    {
        $this->assertSame('hide', $form->get($field)->getConfig()->getOption('attr')['class']);
    }
}
