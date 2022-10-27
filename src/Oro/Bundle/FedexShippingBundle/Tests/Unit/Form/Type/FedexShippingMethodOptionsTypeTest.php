<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexShippingMethodOptionsType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class FedexShippingMethodOptionsTypeTest extends FormIntegrationTestCase
{
    /**
     * @var FedexShippingMethodOptionsType
     */
    private $formType;

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

        $this->formType = new FedexShippingMethodOptionsType($roundingService);
        parent::setUp();
    }

    public function testGetBlockPrefix()
    {
        static::assertEquals('oro_fedex_shipping_method_options', $this->formType->getBlockPrefix());
    }

    public function testSubmit()
    {
        $data = ['surcharge' => 12];
        $submittedData = ['surcharge' => 5];
        $form = $this->factory->create(FedexShippingMethodOptionsType::class, $data);

        static::assertEquals($data, $form->getData());

        $form->submit($submittedData);

        static::assertTrue($form->isValid());
        static::assertTrue($form->isSynchronized());
        static::assertEquals($submittedData, $form->getData());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension([$this->formType], [])
        ];
    }
}
