<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\ShippingBundle\Form\Type\FlatRateShippingMethodTypeOptionsType;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethodType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;

class FlatRateShippingMethodTypeOptionsTypeTest extends FormIntegrationTestCase
{
    /** @var FlatRateShippingMethodTypeOptionsType */
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

        $this->formType = new FlatRateShippingMethodTypeOptionsType($roundingService);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(FlatRateShippingMethodTypeOptionsType::BLOCK_PREFIX, $this->formType->getBlockPrefix());
    }

    public function testSubmitDefaultNull()
    {
        $form = $this->factory->create($this->formType);

        $data = [
            FlatRateShippingMethodType::PRICE_OPTION => '42',
            FlatRateShippingMethodType::TYPE_OPTION => FlatRateShippingMethodType::PER_ITEM_TYPE,
            FlatRateShippingMethodType::HANDLING_FEE_OPTION => 10,
        ];
        $form->submit($data);

        $this->assertTrue($form->isValid());
        $this->assertEquals($data, $form->getData());
    }

    public function testSubmit()
    {
        $form = $this->factory->create($this->formType, [
            FlatRateShippingMethodType::PRICE_OPTION => 1,
            FlatRateShippingMethodType::TYPE_OPTION => FlatRateShippingMethodType::PER_ORDER_TYPE,
            FlatRateShippingMethodType::HANDLING_FEE_OPTION => 2,
        ]);

        $data = [
            FlatRateShippingMethodType::PRICE_OPTION => '42',
            FlatRateShippingMethodType::TYPE_OPTION => FlatRateShippingMethodType::PER_ITEM_TYPE,
            FlatRateShippingMethodType::HANDLING_FEE_OPTION => 10,
        ];
        $form->submit($data);

        $this->assertTrue($form->isValid());
        $this->assertEquals($data, $form->getData());
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }
}
