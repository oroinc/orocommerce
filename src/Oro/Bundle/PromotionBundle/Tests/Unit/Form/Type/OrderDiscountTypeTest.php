<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Model\LocaleSettings;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\PromotionBundle\Discount\ShippingAwareDiscount;
use Oro\Bundle\PromotionBundle\Form\Type\BasicDiscountFormType;
use Oro\Bundle\PromotionBundle\Form\Type\DiscountFreeShippingType;
use Oro\Bundle\PromotionBundle\Form\Type\OrderDiscountType;
use Oro\Bundle\PromotionBundle\Provider\DiscountFormTypeProvider;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;

class OrderDiscountTypeTest extends FormIntegrationTestCase
{
    /**
     * @var DiscountFreeShippingType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();
        $this->formType = new OrderDiscountType();
    }

    public function testGetName()
    {
        $this->assertEquals(OrderDiscountType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(OrderDiscountType::NAME, $this->formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(BasicDiscountFormType::NAME, $this->formType->getParent());
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->formType, null);

        $this->assertTrue($form->has(ShippingAwareDiscount::SHIPPING_DISCOUNT));
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /** @var DiscountFormTypeProvider|\PHPUnit_Framework_MockObject_MockObject $discountFormTypeProvider */
        $discountFormTypeProvider = $this->createMock(DiscountFormTypeProvider::class);
        $discountFormTypeProvider
            ->expects($this->any())
            ->method('getFormTypes')
            ->willReturn([]);

        /** @var RoundingServiceInterface|\PHPUnit_Framework_MockObject_MockObject $roundingService */
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService
            ->expects($this->any())
            ->method('getRoundType')
            ->willReturn(0);

        /** @var CurrencyProviderInterface|\PHPUnit_Framework_MockObject_MockObject $currencyProvider */
        $currencyProvider = $this->createMock(CurrencyProviderInterface::class);

        $currencyProvider
            ->expects($this->any())
            ->method('getCurrencyList')
            ->will($this->returnValue(['USD', 'EUR']));

        /** @var LocaleSettings|\PHPUnit_Framework_MockObject_MockObject $localeSettings  */
        $localeSettings = $this->createMock(LocaleSettings::class);

        /** @var CurrencyNameHelper|\PHPUnit_Framework_MockObject_MockObject $currencyNameHelper */
        $currencyNameHelper = $this->createMock(CurrencyNameHelper::class);

        $configProvider = $this->createMock(ConfigProvider::class);
        $translator = $this->createMock(Translator::class);

        return [
            new PreloadedExtension(
                [
                    DiscountFreeShippingType::NAME => new DiscountFreeShippingType(),
                    PriceType::NAME => new PriceType($roundingService, []),
                    BasicDiscountFormType::NAME => new BasicDiscountFormType($discountFormTypeProvider),
                    CurrencySelectionType::NAME => new CurrencySelectionType(
                        $currencyProvider,
                        $localeSettings,
                        $currencyNameHelper
                    )
                ],
                [
                    'form' => [new TooltipFormExtension($configProvider, $translator)],
                ]
            ),
        ];
    }
}
