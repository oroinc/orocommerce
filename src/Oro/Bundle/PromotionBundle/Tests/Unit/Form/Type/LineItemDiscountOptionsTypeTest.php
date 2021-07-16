<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\MultiCurrencyType;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\OroMoneyType;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitsType;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountProductUnitCodeAwareInterface;
use Oro\Bundle\PromotionBundle\Discount\LineItemsDiscount;
use Oro\Bundle\PromotionBundle\Form\Type\DiscountOptionsType;
use Oro\Bundle\PromotionBundle\Form\Type\LineItemDiscountOptionsType;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class LineItemDiscountOptionsTypeTest extends FormIntegrationTestCase
{
    public function testGetBlockPrefix()
    {
        $formType = new LineItemDiscountOptionsType();
        $this->assertEquals(LineItemDiscountOptionsType::NAME, $formType->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $existingData, array $submittedData, array $expectedData)
    {
        $form = $this->factory->create(LineItemDiscountOptionsType::class, $existingData);
        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);

        $resolver->expects($this->any())
            ->method('setDefault')
            ->with(
                'apply_to_choices',
                [
                    'oro.discount_options.line_item_type.apply_to.choices.each_item' => 'each_item',
                    'oro.discount_options.line_item_type.apply_to.choices.line_items_total' => 'line_items_total',
                ]
            );
        $formType = new LineItemDiscountOptionsType();
        $formType->configureOptions($resolver);
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'create new line item discount' => [
                'existingData' => [],
                'submittedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD => ['value' => '2.0000', 'currency' => 'USD'],
                    LineItemsDiscount::APPLY_TO => LineItemsDiscount::EACH_ITEM,
                    LineItemsDiscount::MAXIMUM_QTY => 10
                ],
                'expectedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    LineItemsDiscount::APPLY_TO => LineItemsDiscount::EACH_ITEM,
                    LineItemsDiscount::MAXIMUM_QTY => 10,
                    AbstractDiscount::DISCOUNT_VALUE => 2,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD'
                ]
            ],
            'edit existing line item discount' => [
                'existingData' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    LineItemsDiscount::APPLY_TO => LineItemsDiscount::EACH_ITEM,
                    LineItemsDiscount::MAXIMUM_QTY => 10,
                    AbstractDiscount::DISCOUNT_VALUE => 2,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD'
                ],
                'submittedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD => ['value' => '100.0000', 'currency' => 'USD'],
                    LineItemsDiscount::APPLY_TO => LineItemsDiscount::LINE_ITEMS_TOTAL,
                    LineItemsDiscount::MAXIMUM_QTY => 5
                ],
                'expectedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    LineItemsDiscount::APPLY_TO => LineItemsDiscount::LINE_ITEMS_TOTAL,
                    LineItemsDiscount::MAXIMUM_QTY => 5,
                    AbstractDiscount::DISCOUNT_VALUE => 100,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD'
                ]
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject $configProvider */
        $configProvider = $this->createMock(ConfigProvider::class);

        /** @var Translator|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->createMock(Translator::class);

        /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject $localeSettings */
        $localeSettings = $this->createMock(LocaleSettings::class);

        /** @var CurrencyNameHelper|\PHPUnit\Framework\MockObject\MockObject $currencyNameHelper */
        $currencyNameHelper = $this->createMock(CurrencyNameHelper::class);

        /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject $numberFormatter */
        $numberFormatter = $this->createMock(NumberFormatter::class);

        /** @var ProductUnitsProvider|\PHPUnit\Framework\MockObject\MockObject $productUnitsProvider */
        $productUnitsProvider = $this->createMock(ProductUnitsProvider::class);
        $productUnitsProvider->expects($this->any())
            ->method('getAvailableProductUnits')
            ->willReturn([
                'oro.product_unit.item.label.full' => 'item',
                'oro.product_unit.set.label.full' => 'set',
            ]);
        /** @var CurrencyProviderInterface|\PHPUnit\Framework\MockObject\MockObject $currencyProvider */
        $currencyProvider = $this->getMockBuilder(CurrencyProviderInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $currencyProvider->expects($this->any())
            ->method('getCurrencyList')
            ->willReturn(['USD', 'EUR']);

        return [
            new PreloadedExtension(
                [
                    ProductUnitsType::class => new ProductUnitsType($productUnitsProvider),
                    DiscountOptionsType::class => new DiscountOptionsType(),
                    MultiCurrencyType::class => new MultiCurrencyType(),
                    CurrencySelectionType::class => new CurrencySelectionType(
                        $currencyProvider,
                        $localeSettings,
                        $currencyNameHelper
                    ),
                    OroMoneyType::class => new OroMoneyType($localeSettings, $numberFormatter)
                ],
                [
                    FormType::class => [new TooltipFormExtension($configProvider, $translator)],
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }
}
