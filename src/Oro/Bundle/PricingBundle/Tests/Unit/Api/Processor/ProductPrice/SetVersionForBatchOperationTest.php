<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\Processor\ProductPrice;

use Oro\Bundle\ApiBundle\Batch\Processor\Update\BatchUpdateContext;
use Oro\Bundle\PricingBundle\Api\Processor\ProductPrice\SetVersionForBatchOperation;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ParameterBag;
use PHPUnit\Framework\TestCase;

class SetVersionForBatchOperationTest extends TestCase
{
    private SetVersionForBatchOperation $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->processor = new SetVersionForBatchOperation();
    }

    public function testProcessWithBatchUpdateContext(): void
    {
        $operationId = 12345;
        $sharedData = new ParameterBag();

        $context = new BatchUpdateContext();
        $context->setOperationId($operationId);
        $context->setSharedData($sharedData);

        $this->processor->process($context);

        self::assertTrue($sharedData->has('batchOperationId'));
        self::assertSame($operationId, $sharedData->get('batchOperationId'));
    }

    public function testProcessWithNonBatchUpdateContext(): void
    {
        $context = $this->createMock(ContextInterface::class);

        // Should not throw any exceptions
        $this->processor->process($context);

        // No assertions needed - we just verify it doesn't crash
        self::assertTrue(true);
    }
}
