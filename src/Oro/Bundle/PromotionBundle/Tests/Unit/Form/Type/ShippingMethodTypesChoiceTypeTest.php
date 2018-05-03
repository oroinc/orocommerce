<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\PromotionBundle\Form\Type\ShippingMethodTypesChoiceType;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodIconProviderInterface;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodStub;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Asset\Packages;

class ShippingMethodTypesChoiceTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ShippingMethodProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $provider;

    /**
     * @var ShippingMethodIconProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $iconProvider;

    /**
     * @var Packages|\PHPUnit_Framework_MockObject_MockObject
     */
    private $assetHelper;

    /**
     * @var ShippingMethodTypesChoiceType
     */
    private $formType;

    protected function setUp()
    {
        $this->provider = $this->createMock(ShippingMethodProviderInterface::class);
        $this->iconProvider = $this->createMock(ShippingMethodIconProviderInterface::class);
        $this->assetHelper = $this->createMock(Packages::class);

        $this->formType = new ShippingMethodTypesChoiceType($this->provider, $this->iconProvider, $this->assetHelper);
        parent::setUp();
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array|null $existingData
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testSubmit($existingData, $submittedData, $expectedData)
    {
        $flatRatePrimaryShippingType = (new ShippingMethodTypeStub())->setIdentifier('primary');

        $flatRateShippingMethod = (new ShippingMethodStub())
            ->setIdentifier('flat_rate_2')
            ->setTypes([$flatRatePrimaryShippingType]);

        $this->provider
            ->expects($this->any())
            ->method('getShippingMethods')
            ->willReturn([$flatRateShippingMethod, $this->getUpsShippingMethod()]);

        $form = $this->factory->create(ShippingMethodTypesChoiceType::class, $existingData);
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

    /**
     * @dataProvider optionsDataProvider
     *
     * @param array $options
     * @param array $expectedOptions
     */
    public function testOptions(array $options, array $expectedOptions)
    {
        $this->provider
            ->expects($this->any())
            ->method('getShippingMethods')
            ->willReturn([$this->getUpsShippingMethod()]);

        $form = $this->factory->create(ShippingMethodTypesChoiceType::class, null, $options);

        $this->assertArraySubset($expectedOptions, $form->getConfig()->getOptions());
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
    {
        return [
            'test default options' => [
                'options' => [],
                'expectedOptions' => [
                    'placeholder' => false,
                    'choices' => [
                        'UPS 2 Day Air' => '{"shipping_method":"ups_4","shipping_method_type":"02"}',
                        'UPS 3 Day Select' => '{"shipping_method":"ups_4","shipping_method_type":"12"}',
                    ],
                    'configs' => [
                        'showIcon' => true,
                        'minimumResultsForSearch' => 1
                    ]
                ]
            ],
        ];
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
     * {@inheritdoc}
     */
    protected function getExtensions()
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

    /**
     * @return ShippingMethodStub|ShippingMethodInterface
     */
    private function getUpsShippingMethod()
    {
        $ups2DayAir = (new ShippingMethodTypeStub())
            ->setIdentifier('02')
            ->setLabel('UPS 2 Day Air');
        $ups3DaySelect = (new ShippingMethodTypeStub())
            ->setIdentifier('12')
            ->setLabel('UPS 3 Day Select');

        return (new ShippingMethodStub())
            ->setIdentifier('ups_4')
            ->setTypes([$ups2DayAir, $ups3DaySelect]);
    }
}
