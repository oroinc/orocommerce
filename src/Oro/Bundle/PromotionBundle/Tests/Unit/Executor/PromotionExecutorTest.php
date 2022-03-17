<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Executor;

use Oro\Bundle\CacheBundle\Generator\ObjectCacheKeyGenerator;
use Oro\Bundle\PromotionBundle\Discount\Converter\DiscountContextConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyInterface;
use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyProvider;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\PromotionBundle\Provider\PromotionDiscountsProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class PromotionExecutorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DiscountContextConverterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $discountContextConverter;

    /**
     * @var StrategyProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $discountStrategyProvider;

    /**
     * @var PromotionDiscountsProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $promotionDiscountsProvider;

    /**
     * @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cache;

    /**
     * @var ObjectCacheKeyGenerator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $objectCacheKeyGenerator;

    /**
     * @var PromotionExecutor
     */
    private $executor;

    protected function setUp(): void
    {
        $this->discountContextConverter = $this->createMock(DiscountContextConverterInterface::class);
        $this->discountStrategyProvider = $this->createMock(StrategyProvider::class);
        $this->promotionDiscountsProvider = $this->createMock(PromotionDiscountsProviderInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->objectCacheKeyGenerator = $this->createMock(ObjectCacheKeyGenerator::class);

        $this->executor = new PromotionExecutor(
            $this->discountContextConverter,
            $this->discountStrategyProvider,
            $this->promotionDiscountsProvider
        );
    }

    public function testExecuteNoDiscounts(): void
    {
        $sourceEntity = new \stdClass();
        $discountContext = new DiscountContext();

        $this->discountContextConverter->expects($this->once())
            ->method('convert')
            ->with($sourceEntity)
            ->willReturn($discountContext);

        $this->promotionDiscountsProvider->expects($this->once())
            ->method('getDiscounts')
            ->with($sourceEntity, $discountContext)
            ->willReturn([]);

        $this->discountStrategyProvider->expects($this->never())
            ->method($this->anything());

        $this->assertSame($discountContext, $this->executor->execute($sourceEntity));
    }

    public function testExecute(): void
    {
        $sourceEntity = new \stdClass();
        $discountContext = new DiscountContext();

        $cacheKey = md5(serialize($sourceEntity));
        $this->objectCacheKeyGenerator->expects($this->once())
            ->method('generate')
            ->with($sourceEntity, 'promotion')
            ->willReturn($cacheKey);
        $this->cache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->injectCache();

        $this->discountContextConverter->expects($this->once())
            ->method('convert')
            ->with($sourceEntity)
            ->willReturn($discountContext);

        $discounts = [$this->createMock(DiscountInterface::class), $this->createMock(DiscountInterface::class)];

        $this->promotionDiscountsProvider->expects($this->once())
            ->method('getDiscounts')
            ->with($sourceEntity, $discountContext)
            ->willReturn($discounts);

        $strategy = $this->createMock(StrategyInterface::class);
        $this->discountStrategyProvider->expects($this->once())
            ->method('getActiveStrategy')
            ->willReturn($strategy);

        $modifiedContext = new DiscountContext();
        $strategy->expects($this->once())
            ->method('process')
            ->with($discountContext, $discounts)
            ->willReturn($modifiedContext);

        $this->assertSame($modifiedContext, $this->executor->execute($sourceEntity));
    }

    public function testExecuteWithCache(): void
    {
        $sourceEntity = new \stdClass();
        $discountContext = new DiscountContext();

        $cacheKey = md5(serialize($sourceEntity));
        $this->objectCacheKeyGenerator->expects($this->once())
            ->method('generate')
            ->with($sourceEntity, 'promotion')
            ->willReturn($cacheKey);
        $this->cache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn($discountContext);

        $this->injectCache();

        $this->discountContextConverter->expects($this->never())
            ->method('convert');
        $this->promotionDiscountsProvider->expects($this->never())
            ->method('getDiscounts');
        $this->discountStrategyProvider->expects($this->never())
            ->method('getActiveStrategy');

        $this->assertSame($discountContext, $this->executor->execute($sourceEntity));
    }

    public function testExecuteWithoutCache(): void
    {
        $sourceEntity = new \stdClass();
        $discountContext = new DiscountContext();

        $this->objectCacheKeyGenerator->expects($this->never())
            ->method('generate');
        $this->cache->expects($this->never())
            ->method('get');

        $this->discountContextConverter->expects($this->once())
            ->method('convert')
            ->with($sourceEntity)
            ->willReturn($discountContext);

        $discounts = [$this->createMock(DiscountInterface::class), $this->createMock(DiscountInterface::class)];

        $this->promotionDiscountsProvider->expects($this->once())
            ->method('getDiscounts')
            ->with($sourceEntity, $discountContext)
            ->willReturn($discounts);

        $strategy = $this->createMock(StrategyInterface::class);
        $this->discountStrategyProvider->expects($this->once())
            ->method('getActiveStrategy')
            ->willReturn($strategy);

        $modifiedContext = new DiscountContext();
        $strategy->expects($this->once())
            ->method('process')
            ->with($discountContext, $discounts)
            ->willReturn($modifiedContext);

        $this->assertSame($modifiedContext, $this->executor->execute($sourceEntity));
    }

    /**
     * @dataProvider trueFalseDataProvider
     */
    public function testSupports(bool $result): void
    {
        $entity = new \stdClass();
        $this->discountContextConverter->expects($this->once())
            ->method('supports')
            ->willReturn($result);

        $this->assertSame($result, $this->executor->supports($entity));
    }

    public function trueFalseDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }

    private function injectCache(): void
    {
        $this->executor->setCache($this->cache);
        $this->executor->setObjectCacheKeyGenerator($this->objectCacheKeyGenerator);
    }
}
