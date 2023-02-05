<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexShippingMethodOptionsType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class FedexShippingMethodOptionsTypeTest extends FormIntegrationTestCase
{
    private FedexShippingMethodOptionsType $formType;

    protected function setUp(): void
    {
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects(self::any())
            ->method('getPrecision')
            ->willReturn(4);
        $roundingService->expects(self::any())
            ->method('getRoundType')
            ->willReturn(RoundingServiceInterface::ROUND_HALF_UP);

        $this->formType = new FedexShippingMethodOptionsType($roundingService);
        parent::setUp();
    }

    public function testGetBlockPrefix()
    {
        self::assertEquals('oro_fedex_shipping_method_options', $this->formType->getBlockPrefix());
    }

    public function testSubmit()
    {
        $data = ['surcharge' => 12];
        $submittedData = ['surcharge' => 5];
        $form = $this->factory->create(FedexShippingMethodOptionsType::class, $data);

        self::assertEquals($data, $form->getData());

        $form->submit($submittedData);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($submittedData, $form->getData());
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->formType], [])
        ];
    }
}
