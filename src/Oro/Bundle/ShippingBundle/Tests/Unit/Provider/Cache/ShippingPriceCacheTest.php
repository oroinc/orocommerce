<?php

namespace Oro\Bundle\ShippingBundle\Provider\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingContextCacheKeyGenerator;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class ShippingPriceCacheTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ShippingPriceCache
     */
    protected $cache;

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
        $this->cacheProvider = $this->getMockBuilder(CacheProvider::class)
            ->setMethods(['fetch', 'contains', 'save', 'deleteAll'])->getMockForAbstractClass();

        $this->keyGenerator = $this->getMock(ShippingContextCacheKeyGenerator::class);
        $this->keyGenerator->expects(static::any())
            ->method('generateKey')
            ->will(static::returnCallback(function (ShippingContextInterface $context) {
                return ($context->getSourceEntity() ? get_class($context->getSourceEntity()) : '')
                    .'_'.$context->getSourceEntityIdentifier();
            }));

        $this->cache = new ShippingPriceCache($this->cacheProvider, $this->keyGenerator);
    }

    /**
     * @dataProvider hasPriceDataProvider
     * @param boolean $isContains
     * @param boolean $hasPrice
     */
    public function testHasPrice($isContains, $hasPrice)
    {
        /** @var ShippingContextInterface $context */
        $context = $this->getEntity(ShippingContext::class);

                $this->cacheProvider->expects(static::once())
            ->method('contains')
            ->with('_flat_rateprimary')
            ->willReturn($isContains);

        static::assertEquals($hasPrice, $this->cache->hasPrice($context, 'flat_rate', 'primary'));
    }


    public function hasPriceDataProvider()
    {
        return [
            [
                'isContains' => true,
                'hasPrice' => true,
            ],
            [
                'isContains' => false,
                'hasPrice' => false,
            ]
        ];
    }

    /**
     * @dataProvider getPriceDataProvider
     * @param boolean $isContains
     * @param Price|null $price
     */
    public function testGetPrice($isContains, Price $price = null)
    {
        /** @var ShippingContextInterface $context */
        $context = $this->getEntity(ShippingContext::class);

        $this->cacheProvider->expects(static::once())
            ->method('contains')
            ->with('_flat_rateprimary')
            ->willReturn($isContains);

        $this->cacheProvider->expects(static::any())
            ->method('fetch')
            ->with('_flat_rateprimary')
            ->willReturn($price);

        static::assertSame($price, $this->cache->getPrice($context, 'flat_rate', 'primary'));
    }

    public function getPriceDataProvider()
    {
        return [
            [
                'isContains' => true,
                'price' => Price::create(5, 'USD'),
            ],
            [
                'isContains' => false,
                'price' => null,
            ]
        ];
    }

    public function testSavePrice()
    {
        /** @var ShippingContextInterface $context */
        $context = $this->getEntity(ShippingContext::class, [
            'sourceEntity' => new \stdClass(),
            'sourceEntityIdentifier' => 1,
        ]);

        $price = Price::create(10, 'USD');

        $this->cacheProvider->expects(static::once())
            ->method('save')
            ->with('stdClass_1flat_rateprimary', $price, ShippingPriceCache::CACHE_LIFETIME)
            ->willReturn($price);

        static::assertEquals($this->cache, $this->cache->savePrice($context, 'flat_rate', 'primary', $price));
    }

    public function testDeleteAllPrices()
    {
        $this->cacheProvider->expects(static::once())
            ->method('deleteAll');

        $this->cache->deleteAllPrices();
    }
}
