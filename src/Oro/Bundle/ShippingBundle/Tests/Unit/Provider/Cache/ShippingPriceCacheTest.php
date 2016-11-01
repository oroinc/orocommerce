<?php

namespace Oro\Bundle\ShippingBundle\Provider\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingContextCacheKeyGenerator;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingRuleRepository;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class ShippingPriceCacheTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ShippingPriceCache
     */
    protected $cache;

    /**
     * @var ShippingRuleRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cacheProvider;

    /**
     * @var ShippingContextCacheKeyGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $keyGenerator;

    public function setUp()
    {
        $doctrine = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder(ShippingRuleRepository::class)
            ->disableOriginalConstructor()->getMock();

        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->with('OroShippingBundle:ShippingRule')
            ->willReturn($this->repository);

        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroShippingBundle:ShippingRule')
            ->willReturn($entityManager);

        $this->cacheProvider = $this->getMockBuilder(CacheProvider::class)
            ->setMethods(['fetch', 'contains', 'save', 'deleteAll'])->getMockForAbstractClass();

        $this->keyGenerator = $this->getMock(ShippingContextCacheKeyGenerator::class);
        $this->keyGenerator->expects(static::any())
            ->method('generateHash')
            ->will(static::returnCallback(function (ShippingContextInterface $context) {
                return ($context->getSourceEntity() ? get_class($context->getSourceEntity()) : '')
                    .'_'.$context->getSourceEntityIdentifier();
            }));

        $this->cache = new ShippingPriceCache($this->cacheProvider, $doctrine, $this->keyGenerator);
    }

    public function testHasPrice()
    {
        $context = $this->getEntity(ShippingContext::class);

        $updateAt = new \DateTime('-5 minutes');

        $this->repository->expects(static::once())
            ->method('getLastUpdateAt')
            ->willReturn($updateAt);

        $this->cacheProvider->expects(static::once())
            ->method('contains')
            ->with(ShippingPriceCache::UPDATED_AT_KEY)
            ->willReturn(true);

        $this->cacheProvider->expects(static::once())
            ->method('fetch')
            ->with(ShippingPriceCache::UPDATED_AT_KEY)
            ->willReturn((new \DateTime('-10 minutes'))->getTimestamp());

        $this->cacheProvider->expects(static::once())
            ->method('deleteAll');

        $this->cacheProvider->expects(static::once())
            ->method('save')
            ->with(ShippingPriceCache::UPDATED_AT_KEY, $updateAt->getTimestamp());

        $this->assertFalse($this->cache->hasPrice($context, 'flat_rate', 'primary'));
    }

    public function testHasPriceCacheDoesNotContain()
    {
        $context = $this->getEntity(ShippingContext::class, []);

        $updateAt = new \DateTime();
        $this->repository->expects(static::once())
            ->method('getLastUpdateAt')
            ->willReturn($updateAt);

        $this->cacheProvider->expects(static::once())
            ->method('contains')
            ->with(ShippingPriceCache::UPDATED_AT_KEY)
            ->willReturn(false);

        $this->cacheProvider->expects(static::never())
            ->method('fetch');

        $this->cacheProvider->expects(static::once())
            ->method('deleteAll');

        $this->cacheProvider->expects(static::once())
            ->method('save')
            ->with(ShippingPriceCache::UPDATED_AT_KEY, $updateAt->getTimestamp());

        $this->assertFalse($this->cache->hasPrice($context, 'flat_rate', 'primary'));
    }

    public function testHasPriceCacheContainsActual()
    {
        $context = $this->getEntity(ShippingContext::class, [
            'sourceEntity' => new \stdClass(),
            'sourceEntityIdentifier' => 1,
        ]);

        $updateAt = new \DateTime();

        $this->repository->expects(static::once())
            ->method('getLastUpdateAt')
            ->willReturn($updateAt);

        $this->cacheProvider->expects(static::at(0))
            ->method('contains')
            ->with(ShippingPriceCache::UPDATED_AT_KEY)
            ->willReturn(true);

        $this->cacheProvider->expects(static::at(1))
            ->method('fetch')
            ->with(ShippingPriceCache::UPDATED_AT_KEY)
            ->willReturn($updateAt->getTimestamp());

        $this->cacheProvider->expects(static::at(2))
            ->method('contains')
            ->with('stdClass_1flat_rateprimary')
            ->willReturn(true);

        $this->assertTrue($this->cache->hasPrice($context, 'flat_rate', 'primary'));
    }

    public function testGetPrice()
    {
        $context = $this->getEntity(ShippingContext::class);

        $updateAt = new \DateTime('-5 minutes');

        $this->repository->expects(static::once())
            ->method('getLastUpdateAt')
            ->willReturn($updateAt);

        $this->cacheProvider->expects(static::once())
            ->method('contains')
            ->with(ShippingPriceCache::UPDATED_AT_KEY)
            ->willReturn(true);

        $this->cacheProvider->expects(static::once())
            ->method('fetch')
            ->with(ShippingPriceCache::UPDATED_AT_KEY)
            ->willReturn((new \DateTime('-10 minutes'))->getTimestamp());

        $this->cacheProvider->expects(static::once())
            ->method('deleteAll');

        $this->cacheProvider->expects(static::once())
            ->method('save')
            ->with(ShippingPriceCache::UPDATED_AT_KEY, $updateAt->getTimestamp());

        $this->assertFalse($this->cache->getPrice($context, 'flat_rate', 'primary'));
    }

    public function testGetPriceCacheDoesNotContain()
    {
        $context = $this->getEntity(ShippingContext::class, []);

        $updateAt = new \DateTime();
        $this->repository->expects(static::once())
            ->method('getLastUpdateAt')
            ->willReturn($updateAt);

        $this->cacheProvider->expects(static::once())
            ->method('contains')
            ->with(ShippingPriceCache::UPDATED_AT_KEY)
            ->willReturn(false);

        $this->cacheProvider->expects(static::never())
            ->method('fetch');

        $this->cacheProvider->expects(static::once())
            ->method('deleteAll');

        $this->cacheProvider->expects(static::once())
            ->method('save')
            ->with(ShippingPriceCache::UPDATED_AT_KEY, $updateAt->getTimestamp());

        $this->assertFalse($this->cache->getPrice($context, 'flat_rate', 'primary'));
    }

    public function testGetPriceCacheContainsActual()
    {
        $context = $this->getEntity(ShippingContext::class, [
            'sourceEntity' => new \stdClass(),
            'sourceEntityIdentifier' => 1,
        ]);

        $updateAt = new \DateTime();

        $this->repository->expects(static::once())
            ->method('getLastUpdateAt')
            ->willReturn($updateAt);

        $this->cacheProvider->expects(static::at(0))
            ->method('contains')
            ->with(ShippingPriceCache::UPDATED_AT_KEY)
            ->willReturn(true);

        $this->cacheProvider->expects(static::at(1))
            ->method('fetch')
            ->with(ShippingPriceCache::UPDATED_AT_KEY)
            ->willReturn($updateAt->getTimestamp());

        $this->cacheProvider->expects(static::at(2))
            ->method('contains')
            ->with('stdClass_1flat_rateprimary')
            ->willReturn(true);

        $price = Price::create(10, 'USD');

        $this->cacheProvider->expects(static::at(3))
            ->method('fetch')
            ->with('stdClass_1flat_rateprimary')
            ->willReturn($price);

        $this->assertEquals($price, $this->cache->getPrice($context, 'flat_rate', 'primary'));
    }

    public function testSavePrice()
    {
        $context = $this->getEntity(ShippingContext::class, [
            'sourceEntity' => new \stdClass(),
            'sourceEntityIdentifier' => 1,
        ]);

        $price = Price::create(10, 'USD');

        $this->cacheProvider->expects(static::once())
            ->method('save')
            ->with('stdClass_1flat_rateprimary', $price, ShippingPriceCache::CACHE_LIFETIME)
            ->willReturn($price);

        $this->assertEquals($this->cache, $this->cache->savePrice($context, 'flat_rate', 'primary', $price));
    }
}
