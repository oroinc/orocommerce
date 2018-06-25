<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice\Processor;

use Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\NormalizeOutputProductPriceId;
use Oro\Bundle\PricingBundle\Api\ProductPrice\ProductPriceIDByContextNormalizerInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use PHPUnit\Framework\TestCase;

class NormalizeOutputProductPriceIdTest extends TestCase
{
    /**
     * @var ProductPriceIDByContextNormalizerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $normalizer;

    /**
     * @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $context;

    /**
     * @var NormalizeOutputProductPriceId
     */
    private $processor;

    protected function setUp()
    {
        $this->normalizer = $this->createMock(ProductPriceIDByContextNormalizerInterface::class);
        $this->context = $this->createMock(ContextInterface::class);

        $this->processor = new NormalizeOutputProductPriceId($this->normalizer);
    }

    public function testProcessNoResult()
    {
        $this->context
            ->expects(static::once())
            ->method('getResult')
            ->willReturn(null);

        $this->normalizer
            ->expects(static::never())
            ->method('normalize');

        $this->processor->process($this->context);
    }

    public function testProcessResultNotArray()
    {
        $this->context
            ->expects(static::once())
            ->method('getResult')
            ->willReturn(1);

        $this->normalizer
            ->expects(static::never())
            ->method('normalize');

        $this->processor->process($this->context);
    }

    public function testProcessOneEntity()
    {
        $oldId = 'id';
        $newId = 'newId-1';

        $this->context
            ->expects(static::once())
            ->method('getResult')
            ->willReturn([
                'id' => $oldId,
            ]);

        $this->context
            ->expects(static::once())
            ->method('setResult')
            ->with([
                'id' => $newId,
            ]);

        $this->normalizer
            ->expects(static::once())
            ->method('normalize')
            ->with($oldId, $this->context)
            ->willReturn($newId);

        $this->processor->process($this->context);
    }

    public function testProcessMultipleEntities()
    {
        $oldIds = [
            ['id' => 'id1'],
            ['id' => 'id2'],
            ['other' => 'id3'],
        ];
        $newIds = [
            ['id' => 'newId-1'],
            ['id' => 'newId-2'],
        ];

        $this->context
            ->expects(static::once())
            ->method('getResult')
            ->willReturn($oldIds);

        $this->context
            ->expects(static::once())
            ->method('setResult')
            ->with([
                $newIds[0],
                $newIds[1],
                $oldIds[2]
            ]);

        $this->normalizer
            ->expects(static::exactly(2))
            ->method('normalize')
            ->withConsecutive(
                [$oldIds[0]['id'], $this->context],
                [$oldIds[1]['id'], $this->context]
            )
            ->willReturnOnConsecutiveCalls(
                $newIds[0]['id'],
                $newIds[1]['id']
            );

        $this->processor->process($this->context);
    }
}
