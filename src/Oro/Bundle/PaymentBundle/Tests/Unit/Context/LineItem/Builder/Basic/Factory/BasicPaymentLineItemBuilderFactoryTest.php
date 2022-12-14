<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context\LineItem\Builder\Basic\Factory;

use Oro\Bundle\PaymentBundle\Context\LineItem\Builder\Basic\BasicPaymentLineItemBuilder;
use Oro\Bundle\PaymentBundle\Context\LineItem\Builder\Basic\Factory\BasicPaymentLineItemBuilderFactory;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;

class BasicPaymentLineItemBuilderFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductUnit|\PHPUnit\Framework\MockObject\MockObject */
    private $productUnit;

    /** @var ProductHolderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productHolder;

    protected function setUp(): void
    {
        $this->productUnit = $this->createMock(ProductUnit::class);
        $this->productHolder = $this->createMock(ProductHolderInterface::class);
    }

    public function testCreate()
    {
        $unitCode = 'someCode';
        $quantity = 15;

        $builderFactory = new BasicPaymentLineItemBuilderFactory();

        $builder = $builderFactory->createBuilder(
            $this->productUnit,
            $unitCode,
            $quantity,
            $this->productHolder
        );

        $expectedBuilder = new BasicPaymentLineItemBuilder(
            $this->productUnit,
            $unitCode,
            $quantity,
            $this->productHolder
        );

        self::assertEquals($expectedBuilder, $builder);
    }
}
