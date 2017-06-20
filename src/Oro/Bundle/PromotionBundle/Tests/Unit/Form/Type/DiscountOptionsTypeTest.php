<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\MultiCurrencyType;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\OroMoneyType;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Form\Type\DiscountOptionsType;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class DiscountOptionsTypeTest extends FormIntegrationTestCase
{
    /**
     * @var DiscountOptionsType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new DiscountOptionsType();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->formType);
    }

    public function testGetName()
    {
        $this->assertEquals(DiscountOptionsType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(DiscountOptionsType::NAME, $this->formType->getBlockPrefix());
    }

    public function testInitialForm()
    {
        $form = $this->factory->create($this->formType);

        $this->assertFormFieldsPreset($form);
        $this->assertFieldIsHidden($form, DiscountOptionsType::PERCENT_DISCOUNT_VALUE_FIELD);
    }

    public function testFormWithTypeAmountSelected()
    {
        $form = $this->factory->create(
            $this->formType,
            [AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_AMOUNT]
        );

        $this->assertFormFieldsPreset($form);
        $this->assertFieldIsHidden($form, DiscountOptionsType::PERCENT_DISCOUNT_VALUE_FIELD);
    }

    public function testFormWithTypePercentSelected()
    {
        $form = $this->factory->create(
            $this->formType,
            [AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_PERCENT]
        );

        $this->assertFormFieldsPreset($form);
        $this->assertFieldIsHidden($form, DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD);
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testSubmit($submittedData, $expectedData)
    {
        $form = $this->factory->create(
            $this->formType,
            []
        );
        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'options for percent discount' => [
                'submittedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_PERCENT,
                    DiscountOptionsType::PERCENT_DISCOUNT_VALUE_FIELD => 123
                ],
                'expectedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_PERCENT,
                    AbstractDiscount::DISCOUNT_VALUE => 123
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

    public function testSetDefaultOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit_Framework_MockObject_MockObject */
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
                            'amount' => 'oro.promotion.form.basic_discount.type.choices.amount',
                            'percent' => 'oro.promotion.form.basic_discount.type.choices.percent'
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

        $this->formType->setDefaultOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /** @var RoundingServiceInterface|\PHPUnit_Framework_MockObject_MockObject $roundingService */
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService
            ->expects($this->any())
            ->method('getRoundType')
            ->willReturn(0);

        /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject $configProvider */
        $configProvider = $this->createMock(ConfigProvider::class);

        /** @var Translator|\PHPUnit_Framework_MockObject_MockObject $translator */
        $translator = $this->createMock(Translator::class);

        /** @var LocaleSettings|\PHPUnit_Framework_MockObject_MockObject $localeSettings */
        $localeSettings = $this->createMock(LocaleSettings::class);

        /** @var NumberFormatter|\PHPUnit_Framework_MockObject_MockObject $numberFormatter */
        $numberFormatter = $this->createMock(NumberFormatter::class);

        return [
            new PreloadedExtension(
                [
                    MultiCurrencyType::NAME => new MultiCurrencyType($roundingService, []),
                    CurrencySelectionType::NAME => new CurrencySelectionTypeStub(),
                    OroMoneyType::NAME => new OroMoneyType($localeSettings, $numberFormatter)
                ],
                [
                    'form' => [new TooltipFormExtension($configProvider, $translator)],
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    /**
     * @param FormInterface $form
     */
    private function assertFormFieldsPreset(FormInterface $form)
    {
        $this->assertTrue($form->has(AbstractDiscount::DISCOUNT_TYPE));
        $this->assertTrue($form->has(DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD));
        $this->assertTrue($form->has(DiscountOptionsType::PERCENT_DISCOUNT_VALUE_FIELD));
    }

    /**
     * @param FormInterface $form
     * @param string $field
     */
    private function assertFieldIsHidden(FormInterface $form, $field)
    {
        $percentDiscountValueField = $form->get($field);
        $expectedPercentDiscountValuedFieldAttr = ['class' => 'hide'];
        $actualPercentDiscountValuedFieldAttr = $percentDiscountValueField->getConfig()->getOption('attr');
        $this->assertArraySubset($expectedPercentDiscountValuedFieldAttr, $actualPercentDiscountValuedFieldAttr);
    }
}
