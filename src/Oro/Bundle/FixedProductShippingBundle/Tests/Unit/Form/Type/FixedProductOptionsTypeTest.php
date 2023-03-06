<?php

namespace Oro\Bundle\FixedProductShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FixedProductShippingBundle\Form\Type\FixedProductOptionsType;
use Oro\Bundle\FixedProductShippingBundle\Method\FixedProductMethodType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Exception\OutOfBoundsException;

class FixedProductOptionsTypeTest extends FormIntegrationTestCase
{
    private FixedProductOptionsType $formType;

    protected function setUp(): void
    {
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects($this->any())
            ->method('getPrecision')
            ->willReturn(4);
        $roundingService->expects($this->any())
            ->method('getRoundType')
            ->willReturn(RoundingServiceInterface::ROUND_HALF_UP);

        $this->formType = new FixedProductOptionsType($roundingService);
        parent::setUp();
    }

    public function testGetBlockPrefix(): void
    {
        $this->assertEquals(FixedProductOptionsType::BLOCK_PREFIX, $this->formType->getBlockPrefix());
    }

    public function testSubmitDefaultNull(): void
    {
        $form = $this->factory->create(FixedProductOptionsType::class);

        $data = [
            FixedProductMethodType::SURCHARGE_TYPE   => FixedProductMethodType::PERCENT,
            FixedProductMethodType::SURCHARGE_ON     => FixedProductMethodType::PRODUCT_SHIPPING_COST,
            FixedProductMethodType::SURCHARGE_AMOUNT => 10,
        ];
        $form->submit($data);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($data, $form->getData());
    }

    public function testSubmitWithPercent(): void
    {
        $form = $this->factory->create(FixedProductOptionsType::class, [
            FixedProductMethodType::SURCHARGE_TYPE   => FixedProductMethodType::PERCENT,
            FixedProductMethodType::SURCHARGE_ON     => FixedProductMethodType::PRODUCT_SHIPPING_COST,
            FixedProductMethodType::SURCHARGE_AMOUNT => 10,
        ]);

        $data = [
            FixedProductMethodType::SURCHARGE_TYPE   => FixedProductMethodType::PERCENT,
            FixedProductMethodType::SURCHARGE_ON     => FixedProductMethodType::PRODUCT_PRICE,
            FixedProductMethodType::SURCHARGE_AMOUNT => 20,
        ];
        $form->submit($data);
        $config = $form->get(FixedProductMethodType::SURCHARGE_AMOUNT)->getConfig();

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($data, $form->getData());
        $this->assertEquals($config->getOption('label'), 'oro.fixed_product.method.surcharge_amount.percent_label');
    }

    public function testSubmitWithFixedAmount(): void
    {
        $form = $this->factory->create(FixedProductOptionsType::class, [
            FixedProductMethodType::SURCHARGE_TYPE   => FixedProductMethodType::FIXED_AMOUNT,
            FixedProductMethodType::SURCHARGE_AMOUNT => 10,
        ]);

        $data = [
            FixedProductMethodType::SURCHARGE_TYPE   => FixedProductMethodType::FIXED_AMOUNT,
            FixedProductMethodType::SURCHARGE_AMOUNT => 20,
        ];
        $form->submit($data);
        $config = $form->get(FixedProductMethodType::SURCHARGE_AMOUNT)->getConfig();

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($data, $form->getData());
        $this->assertEquals($config->getOption('label'), 'oro.fixed_product.method.surcharge_amount.label');

        $this->expectException(OutOfBoundsException::class);
        $form->get(FixedProductMethodType::SURCHARGE_ON);
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
