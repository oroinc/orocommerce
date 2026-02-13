<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\Processor\ProductPrice;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\PricingBundle\Api\Processor\ProductPrice\SetVersionToProductPrice;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ParameterBag;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SetVersionToProductPriceTest extends TestCase
{
    private ShardManager&MockObject $shardManager;
    private SetVersionToProductPrice $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->processor = new SetVersionToProductPrice($this->shardManager);
    }

    public function testProcessWithNonCustomizeFormDataContext(): void
    {
        $context = $this->createMock(ContextInterface::class);

        $this->shardManager->expects(self::never())
            ->method('isShardingEnabled');

        // Should not throw any exceptions
        $this->processor->process($context);
    }

    public function testProcessWhenShardingIsEnabled(): void
    {
        $context = $this->createMock(CustomizeFormDataContext::class);

        $this->shardManager->expects(self::once())
            ->method('isShardingEnabled')
            ->willReturn(true);

        $context->expects(self::never())
            ->method('getSharedData');

        $this->processor->process($context);
    }

    public function testProcessWhenBatchOperationIdIsNull(): void
    {
        $sharedData = new ParameterBag();

        $context = $this->createMock(CustomizeFormDataContext::class);
        $context->expects(self::once())
            ->method('getSharedData')
            ->willReturn($sharedData);
        $this->shardManager->expects(self::once())
            ->method('isShardingEnabled')
            ->willReturn(false);

        $context->expects(self::never())
            ->method('getData');

        $this->processor->process($context);
    }

    public function testProcessSetsVersionToProductPrice(): void
    {
        $batchOperationId = 12345;
        $sharedData = new ParameterBag();
        $sharedData->set('batchOperationId', $batchOperationId);

        $productPrice = $this->createMock(ProductPrice::class);

        $context = $this->createMock(CustomizeFormDataContext::class);
        $context->expects(self::once())
            ->method('getSharedData')
            ->willReturn($sharedData);
        $context->expects(self::once())
            ->method('getData')
            ->willReturn($productPrice);

        $this->shardManager->expects(self::once())
            ->method('isShardingEnabled')
            ->willReturn(false);

        $productPrice->expects(self::once())
            ->method('setVersion')
            ->with($batchOperationId);

        $this->processor->process($context);
    }
}
