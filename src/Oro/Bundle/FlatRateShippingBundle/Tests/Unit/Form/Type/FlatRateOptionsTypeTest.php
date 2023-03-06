<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FlatRateShippingBundle\Form\Type\FlatRateOptionsType;
use Oro\Bundle\FlatRateShippingBundle\Method\FlatRateMethodType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class FlatRateOptionsTypeTest extends FormIntegrationTestCase
{
    /** @var FlatRateOptionsType */
    protected $formType;

    protected function setUp(): void
    {
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects($this->any())
            ->method('getPrecision')
            ->willReturn(4);
        $roundingService->expects($this->any())
            ->method('getRoundType')
            ->willReturn(RoundingServiceInterface::ROUND_HALF_UP);

        $this->formType = new FlatRateOptionsType($roundingService);
        parent::setUp();
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(FlatRateOptionsType::BLOCK_PREFIX, $this->formType->getBlockPrefix());
    }

    public function testSubmitDefaultNull()
    {
        $form = $this->factory->create(FlatRateOptionsType::class);

        $data = [
            FlatRateMethodType::PRICE_OPTION => '42',
            FlatRateMethodType::TYPE_OPTION => FlatRateMethodType::PER_ITEM_TYPE,
            FlatRateMethodType::HANDLING_FEE_OPTION => 10,
        ];
        $form->submit($data);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($data, $form->getData());
    }

    public function testSubmit()
    {
        $form = $this->factory->create(FlatRateOptionsType::class, [
            FlatRateMethodType::PRICE_OPTION => 1,
            FlatRateMethodType::TYPE_OPTION => FlatRateMethodType::PER_ORDER_TYPE,
            FlatRateMethodType::HANDLING_FEE_OPTION => 2,
        ]);

        $data = [
            FlatRateMethodType::PRICE_OPTION => '42',
            FlatRateMethodType::TYPE_OPTION => FlatRateMethodType::PER_ITEM_TYPE,
            FlatRateMethodType::HANDLING_FEE_OPTION => 10,
        ];
        $form->submit($data);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($data, $form->getData());
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->formType], []),
            $this->getValidatorExtension(true)
        ];
    }
}
