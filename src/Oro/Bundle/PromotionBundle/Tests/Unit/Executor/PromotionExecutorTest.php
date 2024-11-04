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
    /** @var DiscountContextConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $discountContextConverter;

    /** @var StrategyProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $discountStrategyProvider;

    /** @var PromotionDiscountsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $promotionDiscountsProvider;

    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var ObjectCacheKeyGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $objectCacheKeyGenerator;

    /** @var PromotionExecutor */
    private $executor;

    #[\Override]
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

    private function injectCache(): void
    {
        $this->executor->setCache($this->cache);
        $this->executor->setObjectCacheKeyGenerator($this->objectCacheKeyGenerator);
    }

    public function testExecuteNoDiscounts(): void
    {
        $sourceEntity = new \stdClass();
        $discountContext = new DiscountContext();

        $this->discountContextConverter->expects(self::once())
            ->method('convert')
            ->with($sourceEntity)
            ->willReturn($discountContext);

        $this->promotionDiscountsProvider->expects(self::once())
            ->method('getDiscounts')
            ->with($sourceEntity, $discountContext)
            ->willReturn([]);

        $this->discountStrategyProvider->expects(self::never())
            ->method($this->anything());

        self::assertSame($discountContext, $this->executor->execute($sourceEntity));
    }

    public function testExecuteWhenDataNotCached(): void
    {
        $sourceEntity = new \stdClass();
        $discountContext = new DiscountContext();
        $cacheKey = 'cache_key';

        $this->objectCacheKeyGenerator->expects(self::once())
            ->method('generate')
            ->with($sourceEntity, 'promotion')
            ->willReturn($cacheKey);
        $this->cache->expects(self::once())
            ->method('get')
            ->with($cacheKey)
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        $this->injectCache();

        $this->discountContextConverter->expects(self::once())
            ->method('convert')
            ->with($sourceEntity)
            ->willReturn($discountContext);

        $discounts = [$this->createMock(DiscountInterface::class), $this->createMock(DiscountInterface::class)];

        $this->promotionDiscountsProvider->expects(self::once())
            ->method('getDiscounts')
            ->with($sourceEntity, $discountContext)
            ->willReturn($discounts);

        $strategy = $this->createMock(StrategyInterface::class);
        $this->discountStrategyProvider->expects(self::once())
            ->method('getActiveStrategy')
            ->willReturn($strategy);

        $modifiedContext = new DiscountContext();
        $strategy->expects(self::once())
            ->method('process')
            ->with($discountContext, $discounts)
            ->willReturn($modifiedContext);

        self::assertSame($modifiedContext, $this->executor->execute($sourceEntity));
    }

    public function testExecuteWhenDataCached(): void
    {
        $sourceEntity = new \stdClass();
        $discountContext = new DiscountContext();
        $cacheKey = 'cache_key';

        $this->objectCacheKeyGenerator->expects(self::once())
            ->method('generate')
            ->with($sourceEntity, 'promotion')
            ->willReturn($cacheKey);
        $this->cache->expects(self::once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn($discountContext);

        $this->injectCache();

        $this->discountContextConverter->expects(self::never())
            ->method('convert');
        $this->promotionDiscountsProvider->expects(self::never())
            ->method('getDiscounts');
        $this->discountStrategyProvider->expects(self::never())
            ->method('getActiveStrategy');

        self::assertSame($discountContext, $this->executor->execute($sourceEntity));
    }

    public function testExecuteWithoutCache(): void
    {
        $sourceEntity = new \stdClass();
        $discountContext = new DiscountContext();

        $this->objectCacheKeyGenerator->expects(self::never())
            ->method('generate');
        $this->cache->expects(self::never())
            ->method('get');

        $this->discountContextConverter->expects(self::once())
            ->method('convert')
            ->with($sourceEntity)
            ->willReturn($discountContext);

        $discounts = [$this->createMock(DiscountInterface::class), $this->createMock(DiscountInterface::class)];

        $this->promotionDiscountsProvider->expects(self::once())
            ->method('getDiscounts')
            ->with($sourceEntity, $discountContext)
            ->willReturn($discounts);

        $strategy = $this->createMock(StrategyInterface::class);
        $this->discountStrategyProvider->expects(self::once())
            ->method('getActiveStrategy')
            ->willReturn($strategy);

        $modifiedContext = new DiscountContext();
        $strategy->expects(self::once())
            ->method('process')
            ->with($discountContext, $discounts)
            ->willReturn($modifiedContext);

        self::assertSame($modifiedContext, $this->executor->execute($sourceEntity));
    }

    public function testExecuteWhenNoActiveStrategy(): void
    {
        $sourceEntity = new \stdClass();
        $discountContext = new DiscountContext();

        $this->objectCacheKeyGenerator->expects(self::never())
            ->method('generate');
        $this->cache->expects(self::never())
            ->method('get');

        $this->discountContextConverter->expects(self::once())
            ->method('convert')
            ->with($sourceEntity)
            ->willReturn($discountContext);

        $discounts = [$this->createMock(DiscountInterface::class), $this->createMock(DiscountInterface::class)];

        $this->promotionDiscountsProvider->expects(self::once())
            ->method('getDiscounts')
            ->with($sourceEntity, $discountContext)
            ->willReturn($discounts);

        $this->discountStrategyProvider->expects(self::once())
            ->method('getActiveStrategy')
            ->willReturn(null);

        self::assertSame($discountContext, $this->executor->execute($sourceEntity));
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(bool $result): void
    {
        $entity = new \stdClass();
        $this->discountContextConverter->expects(self::once())
            ->method('supports')
            ->willReturn($result);

        self::assertSame($result, $this->executor->supports($entity));
    }

    public function supportsDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }
}
