<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\UPSBundle\Form\Type\UPSShippingMethodOptionsType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class UPSShippingMethodOptionsTypeTest extends FormIntegrationTestCase
{
    /** @var UPSShippingMethodOptionsType */
    protected $formType;

    protected function setUp(): void
    {
        /** @var RoundingServiceInterface|\PHPUnit\Framework\MockObject\MockObject $roundingService */
        $roundingService = $this->getMockForAbstractClass(RoundingServiceInterface::class);
        $roundingService->expects(static::any())
            ->method('getPrecision')
            ->willReturn(4);
        $roundingService->expects(static::any())
            ->method('getRoundType')
            ->willReturn(RoundingServiceInterface::ROUND_HALF_UP);

        $this->formType = new UPSShippingMethodOptionsType($roundingService);
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    UPSShippingMethodOptionsType::class => $this->formType
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    public function testGetBlockPrefix()
    {
        static::assertEquals(UPSShippingMethodOptionsType::BLOCK_PREFIX, $this->formType->getBlockPrefix());
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
        $form = $this->factory->create(UPSShippingMethodOptionsType::class, $defaultData);

        static::assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        static::assertTrue($form->isValid());
        static::assertTrue($form->isSynchronized());
        static::assertEquals($expectedData, $form->getData());
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
