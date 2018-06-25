<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice;

use Oro\Bundle\PricingBundle\Api\ProductPrice\PriceListIDContextStorageInterface;
use Oro\Bundle\PricingBundle\Api\ProductPrice\ProductPriceIDByPriceListIDNormalizer;
use Oro\Component\ChainProcessor\ContextInterface;
use PHPUnit\Framework\TestCase;

class ProductPriceIDByPriceListIDNormalizerTest extends TestCase
{
    /**
     * @var PriceListIDContextStorageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $priceListIDContextStorage;

    /**
     * @var ProductPriceIDByPriceListIDNormalizer
     */
    private $normalizer;

    protected function setUp()
    {
        $this->priceListIDContextStorage = $this->createMock(PriceListIDContextStorageInterface::class);

        $this->normalizer = new ProductPriceIDByPriceListIDNormalizer(
            $this->priceListIDContextStorage
        );
    }

    public function testProcess()
    {
        $id = 'id';
        $priceListId = 26;

        $context = $this->createMock(ContextInterface::class);

        $this->priceListIDContextStorage
            ->expects(static::once())
            ->method('get')
            ->with($context)
            ->willReturn($priceListId);

        static::assertSame(
            $id . '-' . $priceListId,
            $this->normalizer->normalize($id, $context)
        );
    }
}
