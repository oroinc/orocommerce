<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\UPSBundle\Form\Type\UPSShippingMethodOptionsType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class UPSShippingMethodOptionsTypeTest extends FormIntegrationTestCase
{
    /** @var UPSShippingMethodOptionsType */
    private $formType;

    protected function setUp(): void
    {
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects(self::any())
            ->method('getPrecision')
            ->willReturn(4);
        $roundingService->expects(self::any())
            ->method('getRoundType')
            ->willReturn(RoundingServiceInterface::ROUND_HALF_UP);

        $this->formType = new UPSShippingMethodOptionsType($roundingService);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    UPSShippingMethodOptionsType::class => $this->formType
                ],
                [
                    NumberType::class => [new TooltipFormExtensionStub($this)],
                ]
            ),
            $this->getValidatorExtension(true),
        ];
    }

    public function testGetBlockPrefix()
    {
        self::assertEquals(UPSShippingMethodOptionsType::BLOCK_PREFIX, $this->formType->getBlockPrefix());
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(array $submittedData, mixed $expectedData, mixed $defaultData = null)
    {
        $form = $this->factory->create(UPSShippingMethodOptionsType::class, $defaultData);

        self::assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($expectedData, $form->getData());
    }

    public function submitProvider(): array
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
