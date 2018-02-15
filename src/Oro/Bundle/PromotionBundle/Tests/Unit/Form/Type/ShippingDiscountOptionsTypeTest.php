<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\Select2Type;
use Oro\Bundle\CurrencyBundle\Form\Type\MultiCurrencyType;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\OroMoneyType;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\PayPalBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\PromotionBundle\Form\Type\DiscountOptionsType;
use Oro\Bundle\PromotionBundle\Form\Type\ShippingDiscountOptionsType;
use Oro\Bundle\PromotionBundle\Form\Type\ShippingMethodTypesChoiceType;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodIconProviderInterface;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodStub;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodTypeStub;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;

class ShippingDiscountOptionsTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ShippingDiscountOptionsType
     */
    private $formType;

    protected function setUp()
    {
        parent::setUp();
        $this->formType = new ShippingDiscountOptionsType();
    }

    public function testGetName()
    {
        $this->assertEquals(ShippingDiscountOptionsType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ShippingDiscountOptionsType::NAME, $this->formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(DiscountOptionsType::NAME, $this->formType->getParent());
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->formType, null);

        $this->assertTrue($form->has(ShippingDiscount::SHIPPING_OPTIONS));
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $existingData
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testSubmit(array $existingData, array $submittedData, array $expectedData)
    {
        $form = $this->factory->create($this->formType, $existingData);
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
            'create new discount' => [
                'existingData' => [],
                'submittedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD => ['value' => '2.0000', 'currency' => 'USD'],
                    ShippingDiscount::SHIPPING_OPTIONS => null,
                    ShippingDiscount::SHIPPING_OPTIONS =>
                        '{"shipping_method":"flat_rate_2","shipping_method_type":"primary"}'
                ],
                'expectedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 2,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                    ShippingDiscount::SHIPPING_OPTIONS => [
                        ShippingDiscount::SHIPPING_METHOD => 'flat_rate_2',
                        ShippingDiscount::SHIPPING_METHOD_TYPE => 'primary'
                    ]
                ]
            ],
            'edit existing discount' => [
                'existingData' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 3,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'EUR',
                    ShippingDiscount::SHIPPING_OPTIONS => [
                        ShippingDiscount::SHIPPING_METHOD => 'flat_rate_2',
                        ShippingDiscount::SHIPPING_METHOD_TYPE => 'primary'
                    ]
                ],
                'submittedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD => ['value' => '7.0000', 'currency' => 'USD'],
                    ShippingDiscount::SHIPPING_OPTIONS =>
                        '{"shipping_method":"flat_rate_2","shipping_method_type":"secondary"}'
                ],
                'expectedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 7,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                    ShippingDiscount::SHIPPING_OPTIONS => [
                        ShippingDiscount::SHIPPING_METHOD => 'flat_rate_2',
                        ShippingDiscount::SHIPPING_METHOD_TYPE => 'secondary'
                    ]
                ]
            ]
        ];
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

        /** @var ShippingMethodProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->createMock(ShippingMethodProviderInterface::class);

        /** @var ShippingMethodIconProviderInterface|\PHPUnit_Framework_MockObject_MockObject $iconProvider */
        $iconProvider = $this->createMock(ShippingMethodIconProviderInterface::class);

        /** @var Packages|\PHPUnit_Framework_MockObject_MockObject $assetHelper */
        $assetHelper = $this->createMock(Packages::class);

        $flatRatePrimaryShippingType = (new ShippingMethodTypeStub())->setIdentifier('primary');
        $flatRateSecondaryShippingType = (new ShippingMethodTypeStub())->setIdentifier('secondary');

        $flatRateShippingMethod = (new ShippingMethodStub())
            ->setIdentifier('flat_rate_2')
            ->setTypes([$flatRatePrimaryShippingType, $flatRateSecondaryShippingType]);

        $provider
            ->expects($this->any())
            ->method('getShippingMethods')
            ->willReturn([$flatRateShippingMethod]);

        return [
            new PreloadedExtension(
                [
                    MultiCurrencyType::NAME => new MultiCurrencyType($roundingService, []),
                    CurrencySelectionType::NAME => new CurrencySelectionTypeStub(),
                    DiscountOptionsType::NAME => new DiscountOptionsType(),
                    OroMoneyType::NAME => new OroMoneyType($localeSettings, $numberFormatter),
                    ShippingMethodTypesChoiceType::NAME =>
                        new ShippingMethodTypesChoiceType($provider, $iconProvider, $assetHelper),
                    'oro_select2_choice' => new Select2Type(
                        'Symfony\Component\Form\Extension\Core\Type\ChoiceType',
                        'oro_select2_choice'
                    ),
                ],
                [
                    'form' => [new TooltipFormExtension($configProvider, $translator)],
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }
}
