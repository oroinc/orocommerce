<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\SaveProductPrice;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Manager\PriceManager;

class SaveProductPriceTest extends FormProcessorTestCase
{
    /** @var PriceManager|\PHPUnit\Framework\MockObject\MockObject */
    private $priceManager;

    /** @var SaveProductPrice */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->priceManager = $this->createMock(PriceManager::class);

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
        $productPrice->expects(self::once())
            ->method('getId')
            ->willReturn($productPriceId);

        $this->priceManager->expects(self::once())
            ->method('persist')
            ->with($productPrice);
        $this->priceManager->expects(self::once())
            ->method('flush');

        $this->context->setResult($productPrice);
        $this->processor->process($this->context);
        self::assertSame($productPriceId, $this->context->getId());
    }
}
