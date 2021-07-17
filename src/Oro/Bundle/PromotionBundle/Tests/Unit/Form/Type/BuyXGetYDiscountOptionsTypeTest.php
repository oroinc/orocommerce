<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\MultiCurrencyType;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\OroMoneyType;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitsType;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\BuyXGetYDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountProductUnitCodeAwareInterface;
use Oro\Bundle\PromotionBundle\Form\Type\BuyXGetYDiscountOptionsType;
use Oro\Bundle\PromotionBundle\Form\Type\DiscountOptionsType;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class BuyXGetYDiscountOptionsTypeTest extends FormIntegrationTestCase
{
    /**
     * @var BuyXGetYDiscountOptionsType
     */
    private $formType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formType = new BuyXGetYDiscountOptionsType();
    }

    public function testGetName()
    {
        $this->assertEquals(BuyXGetYDiscountOptionsType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(BuyXGetYDiscountOptionsType::NAME, $this->formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(DiscountOptionsType::class, $this->formType->getParent());
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $existingData, array $submittedData, array $expectedData)
    {
        $form = $this->factory->create(BuyXGetYDiscountOptionsType::class, $existingData);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'create new buy x get y discount' => [
                'existingData' => [],
                'submittedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD => ['value' => 5.0, 'currency' => 'USD'],
                    BuyXGetYDiscount::DISCOUNT_APPLY_TO => BuyXGetYDiscount::APPLY_TO_EACH_Y,
                    BuyXGetYDiscount::DISCOUNT_LIMIT => 10,
                    BuyXGetYDiscount::BUY_X => 4,
                    BuyXGetYDiscount::GET_Y => 3,
                ],
                'expectedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    BuyXGetYDiscount::DISCOUNT_APPLY_TO => BuyXGetYDiscount::APPLY_TO_EACH_Y,
                    BuyXGetYDiscount::DISCOUNT_LIMIT => 10,
                    AbstractDiscount::DISCOUNT_VALUE => 5.0,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                    BuyXGetYDiscount::BUY_X => 4,
                    BuyXGetYDiscount::GET_Y => 3,
                ],
            ],
            'edit existing buy x get y discount' => [
                'existingData' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'item',
                    BuyXGetYDiscount::DISCOUNT_APPLY_TO => BuyXGetYDiscount::APPLY_TO_EACH_Y,
                    BuyXGetYDiscount::DISCOUNT_LIMIT => 10,
                    AbstractDiscount::DISCOUNT_VALUE => 5,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                    BuyXGetYDiscount::BUY_X => 4,
                    BuyXGetYDiscount::GET_Y => 3,
                ],
                'submittedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'set',
                    DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD => ['value' => 10.0, 'currency' => 'USD'],
                    BuyXGetYDiscount::DISCOUNT_APPLY_TO => BuyXGetYDiscount::APPLY_TO_XY_TOTAL,
                    BuyXGetYDiscount::DISCOUNT_LIMIT => 5,
                    BuyXGetYDiscount::BUY_X => 6,
                    BuyXGetYDiscount::GET_Y => 5,
                ],
                'expectedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    DiscountProductUnitCodeAwareInterface::DISCOUNT_PRODUCT_UNIT_CODE => 'set',
                    BuyXGetYDiscount::DISCOUNT_APPLY_TO => BuyXGetYDiscount::APPLY_TO_XY_TOTAL,
                    BuyXGetYDiscount::DISCOUNT_LIMIT => 5,
                    AbstractDiscount::DISCOUNT_VALUE => 10.0,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                    BuyXGetYDiscount::BUY_X => 6,
                    BuyXGetYDiscount::GET_Y => 5,
                ],
            ],
        ];
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
                    'oro.discount_options.buy_x_get_y_type.apply_to.choices.apply_to_each_y' => 'apply_to_each_y',
                    'oro.discount_options.buy_x_get_y_type.apply_to.choices.apply_to_xy_total' => 'apply_to_xy_total',
                ]
            );
        $this->formType->configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /** @var ProductUnitsProvider|\PHPUnit\Framework\MockObject\MockObject $productUnitsProvider */
        $productUnitsProvider = $this->createMock(ProductUnitsProvider::class);
        $productUnitsProvider->expects($this->any())
            ->method('getAvailableProductUnits')
            ->willReturn([
                'oro.product_unit.item.label.full' => 'item',
                'oro.product_unit.set.label.full' => 'set',
            ]);

        /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject $localeSettings */
        $localeSettings = $this->createMock(LocaleSettings::class);
        /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject $numberFormatter */
        $numberFormatter = $this->createMock(NumberFormatter::class);

        /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject $configProvider */
        $configProvider = $this->createMock(ConfigProvider::class);
        /** @var Translator|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->createMock(Translator::class);

        return [
            new PreloadedExtension(
                [
                    ProductUnitsType::class => new ProductUnitsType($productUnitsProvider),
                    DiscountOptionsType::class => new DiscountOptionsType(),
                    MultiCurrencyType::class => new MultiCurrencyType(),
                    CurrencySelectionType::class => new CurrencySelectionTypeStub(),
                    OroMoneyType::class => new OroMoneyType($localeSettings, $numberFormatter),
                ],
                [
                    FormType::class => [new TooltipFormExtension($configProvider, $translator)],
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }
}
