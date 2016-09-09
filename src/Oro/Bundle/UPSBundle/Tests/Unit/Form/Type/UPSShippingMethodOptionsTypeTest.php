<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\UPSBundle\Form\Type\UPSShippingMethodOptionsType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class UPSShippingMethodOptionsTypeTest extends FormIntegrationTestCase
{
    /** @var UPSShippingMethodOptionsType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $roundingService = $this->getMock(RoundingServiceInterface::class);
        $roundingService->expects($this->any())
            ->method('getPrecision')
            ->willReturn(4);
        $roundingService->expects($this->any())
            ->method('getRoundType')
            ->willReturn(RoundingServiceInterface::ROUND_HALF_UP);

        $this->formType = new UPSShippingMethodOptionsType($roundingService);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(UPSShippingMethodOptionsType::BLOCK_PREFIX, $this->formType->getBlockPrefix());
    }

    /**
     * @param array $submittedData
     * @param mixed $expectedData
     * @param mixed $defaultData
     *
     * @dataProvider submitProvider
     */
    public function testSubmit($submittedData, $expectedData, $defaultData = null)
    {
        $form = $this->factory->create($this->formType, $defaultData);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'empty default data' => [
                'submittedData' => [
                    'surcharge' => 10,
                ],
                'expectedData' => [
                    'surcharge' => 10,
                ]
            ],
            'full data' => [
                'submittedData' => [
                    'surcharge' => 10,
                ],
                'expectedData' => [
                    'surcharge' => 10,
                ],
                'defaultData' => [
                    'surcharge' => 12,
                ],
            ],
        ];
    }
}
