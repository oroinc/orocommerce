<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\SaveProductPrice;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use PHPUnit\Framework\TestCase;

class SaveProductPriceTest extends TestCase
{
    /**
     * @var PriceManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $priceManager;

    /**
     * @var SingleItemContext|\PHPUnit\Framework\MockObject\MockObject
     */
    private $context;

    /**
     * @var SaveProductPrice
     */
    private $processor;

    protected function setUp()
    {
        $this->priceManager = $this->createMock(PriceManager::class);
        $this->context = $this->createMock(SingleItemContext::class);

        $this->processor = new SaveProductPrice($this->priceManager);
    }

    public function testProcessWrongEntity()
    {
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $productPriceId = 'test';

        $productPrice = $this->createMock(ProductPrice::class);
        $productPrice
            ->expects(static::once())
            ->method('getId')
            ->willReturn($productPriceId);

        $this->context
            ->expects(static::once())
            ->method('getResult')
            ->willReturn($productPrice);
        $this->context
            ->expects(static::once())
            ->method('setId')
            ->with($productPriceId);

        $this->priceManager
            ->expects(static::once())
            ->method('persist')
            ->with($productPrice);
        $this->priceManager
            ->expects(static::once())
            ->method('flush');

        $this->processor->process($this->context);
    }
}
