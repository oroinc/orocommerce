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
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\PromotionBundle\Form\Type\DiscountOptionsType;
use Oro\Bundle\PromotionBundle\Form\Type\ShippingDiscountOptionsType;
use Oro\Bundle\PromotionBundle\Form\Type\ShippingMethodTypesChoiceType;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodChoicesProvider;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodIconProviderInterface;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodStub;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

class ShippingDiscountOptionsTypeTest extends FormIntegrationTestCase
{
    public function testGetBlockPrefix()
    {
        $formType = new ShippingDiscountOptionsType();
        $this->assertEquals('oro_promotion_shipping_discount_options', $formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        $formType = new ShippingDiscountOptionsType();
        $this->assertEquals(DiscountOptionsType::class, $formType->getParent());
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(ShippingDiscountOptionsType::class);

        $this->assertTrue($form->has(ShippingDiscount::SHIPPING_OPTIONS));
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $existingData, array $submittedData, array $expectedData)
    {
        $form = $this->factory->create(ShippingDiscountOptionsType::class, $existingData);
        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        return [
            'create new discount' => [
                'existingData' => [],
                'submittedData' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    DiscountOptionsType::AMOUNT_DISCOUNT_VALUE_FIELD => ['value' => '2.0000', 'currency' => 'USD'],
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
    protected function getExtensions(): array
    {
        $localeSettings = $this->createMock(LocaleSettings::class);
        $numberFormatter = $this->createMock(NumberFormatter::class);
        $provider = $this->createMock(ShippingMethodChoicesProvider::class);
        $iconProvider = $this->createMock(ShippingMethodIconProviderInterface::class);
        $assetHelper = $this->createMock(Packages::class);

        $flatRatePrimaryShippingType = new ShippingMethodTypeStub();
        $flatRatePrimaryShippingType->setIdentifier('primary');
        $flatRatePrimaryShippingType->setLabel('Flat Rate Shipping (Primary)');
        $flatRateSecondaryShippingType = new ShippingMethodTypeStub();
        $flatRateSecondaryShippingType->setIdentifier('secondary');
        $flatRateSecondaryShippingType->setLabel('Flat Rate Shipping (Secondary)');

        $flatRateShippingMethod = new ShippingMethodStub();
        $flatRateShippingMethod->setIdentifier('flat_rate_2');
        $flatRateShippingMethod->setLabel('Flat Rate Shipping');
        $flatRateShippingMethod->setTypes([$flatRatePrimaryShippingType, $flatRateSecondaryShippingType]);

        $provider->expects($this->any())
            ->method('getMethodTypes')
            ->willReturn([
                $flatRatePrimaryShippingType->getLabel() => json_encode([
                    ShippingDiscount::SHIPPING_METHOD => $flatRateShippingMethod->getIdentifier(),
                    ShippingDiscount::SHIPPING_METHOD_TYPE => $flatRatePrimaryShippingType->getIdentifier()
                ]),
                $flatRateSecondaryShippingType->getLabel() => json_encode([
                    ShippingDiscount::SHIPPING_METHOD => $flatRateShippingMethod->getIdentifier(),
                    ShippingDiscount::SHIPPING_METHOD_TYPE => $flatRateSecondaryShippingType->getIdentifier()
                ]),
            ]);

        return [
            new PreloadedExtension(
                [
                    new MultiCurrencyType(),
                    new DiscountOptionsType(),
                    new OroMoneyType($localeSettings, $numberFormatter),
                    new ShippingMethodTypesChoiceType($provider, $iconProvider, $assetHelper),
                    CurrencySelectionType::class => new CurrencySelectionTypeStub(),
                ],
                [
                    FormType::class => [new TooltipFormExtensionStub($this)]
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }
}
