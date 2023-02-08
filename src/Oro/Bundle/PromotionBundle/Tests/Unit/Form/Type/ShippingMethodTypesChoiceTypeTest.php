<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\PromotionBundle\Form\Type\ShippingMethodTypesChoiceType;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodIconProviderInterface;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodStub;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Asset\Packages;

class ShippingMethodTypesChoiceTypeTest extends FormIntegrationTestCase
{
    /** @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var ShippingMethodIconProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $iconProvider;

    /** @var Packages|\PHPUnit\Framework\MockObject\MockObject */
    private $assetHelper;

    /** @var ShippingMethodTypesChoiceType */
    private $formType;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(ShippingMethodProviderInterface::class);
        $this->iconProvider = $this->createMock(ShippingMethodIconProviderInterface::class);
        $this->assetHelper = $this->createMock(Packages::class);

        $this->formType = new ShippingMethodTypesChoiceType($this->provider, $this->iconProvider, $this->assetHelper);

        parent::setUp();
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(?array $existingData, string $submittedData, array $expectedData)
    {
        $flatRatePrimaryShippingType = new ShippingMethodTypeStub();
        $flatRatePrimaryShippingType->setIdentifier('primary');

        $flatRateShippingMethod = new ShippingMethodStub();
        $flatRateShippingMethod->setIdentifier('flat_rate_2');
        $flatRateShippingMethod->setTypes([$flatRatePrimaryShippingType]);

        $this->provider->expects($this->any())
            ->method('getShippingMethods')
            ->willReturn([$flatRateShippingMethod, $this->getUpsShippingMethod()]);

        $form = $this->factory->create(ShippingMethodTypesChoiceType::class, $existingData);
        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        return [
            'create new value' => [
                'existingData' => null,
                'submittedData' => '{"shipping_method":"flat_rate_2","shipping_method_type":"primary"}',
                'expectedData' => [
                    ShippingDiscount::SHIPPING_METHOD => 'flat_rate_2',
                    ShippingDiscount::SHIPPING_METHOD_TYPE => 'primary',
                ]
            ],
            'edit existing value' => [
                'existingData' => [
                    ShippingDiscount::SHIPPING_METHOD => 'ups_4',
                    ShippingDiscount::SHIPPING_METHOD_TYPE => '02',
                ],
                'submittedData' => '{"shipping_method":"ups_4","shipping_method_type":"12"}',
                'expectedData' => [
                    ShippingDiscount::SHIPPING_METHOD => 'ups_4',
                    ShippingDiscount::SHIPPING_METHOD_TYPE => '12',
                ]
            ]
        ];
    }

    public function testDefaultOptions()
    {
        $options = [];
        $this->provider->expects($this->any())
            ->method('getShippingMethods')
            ->willReturn([$this->getUpsShippingMethod()]);

        $form = $this->factory->create(ShippingMethodTypesChoiceType::class, null, $options);

        $formOptions = $form->getConfig()->getOptions();

        $this->assertNull($formOptions['placeholder']);
        $this->assertTrue($formOptions['configs']['showIcon']);
        $this->assertSame(1, $formOptions['configs']['minimumResultsForSearch']);
        $this->assertSame([
            'UPS 2 Day Air' => '{"shipping_method":"ups_4","shipping_method_type":"02"}',
            'UPS 3 Day Select' => '{"shipping_method":"ups_4","shipping_method_type":"12"}',
        ], $formOptions['choices']);
    }

    public function testGetParent()
    {
        $this->assertEquals(OroChoiceType::class, $this->formType->getParent());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ShippingMethodTypesChoiceType::NAME, $this->formType->getBlockPrefix());
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    ShippingMethodTypesChoiceType::class => $this->formType
                ],
                []
            )
        ];
    }

    private function getUpsShippingMethod(): ShippingMethodStub
    {
        $ups2DayAir = new ShippingMethodTypeStub();
        $ups2DayAir->setIdentifier('02');
        $ups2DayAir->setLabel('UPS 2 Day Air');

        $ups3DaySelect = new ShippingMethodTypeStub();
        $ups3DaySelect->setIdentifier('12');
        $ups3DaySelect->setLabel('UPS 3 Day Select');

        $upsShippingMethod = new ShippingMethodStub();
        $upsShippingMethod->setIdentifier('ups_4');
        $upsShippingMethod->setTypes([$ups2DayAir, $ups3DaySelect]);

        return $upsShippingMethod;
    }
}
