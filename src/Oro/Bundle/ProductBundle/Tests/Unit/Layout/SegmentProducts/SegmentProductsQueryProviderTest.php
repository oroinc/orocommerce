<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\SegmentProducts;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Layout\SegmentProducts\SegmentProductsQueryCache;
use Oro\Bundle\ProductBundle\Layout\SegmentProducts\SegmentProductsQueryProvider;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SegmentProductsQueryProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteManager;

    /** @var SegmentManager|\PHPUnit\Framework\MockObject\MockObject */
    private $segmentManager;

    /** @var ProductManager|\PHPUnit\Framework\MockObject\MockObject */
    private $productManager;

    /** @var SegmentProductsQueryCache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var SegmentProductsQueryProvider */
    private $segmentProductsQueryProvider;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->segmentManager = $this->createMock(SegmentManager::class);
        $this->productManager = $this->createMock(ProductManager::class);
        $this->cache = $this->createMock(SegmentProductsQueryCache::class);

        $this->segmentProductsQueryProvider = new SegmentProductsQueryProvider(
            $this->tokenStorage,
            $this->websiteManager,
            $this->segmentManager,
            $this->productManager,
            $this->cache
        );
    }

    private function getSegment(): Segment
    {
        $segment = new Segment();
        ReflectionUtil::setId($segment, 42);
        $segment->setRecordsLimit(25);

        return $segment;
    }

    private function getQuery(): Query
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::any())
            ->method('getConfiguration')
            ->willReturn(new Configuration());

        return new Query($em);
    }

    private function setCacheKeyExpectations(): void
    {
        $user = $this->createMock(CustomerUser::class);
        $token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $user->expects(self::once())
            ->method('getId')
            ->willReturn(55);

        $website = $this->createMock(Website::class);
        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $website->expects(self::once())
            ->method('getId')
            ->willReturn(100);
    }

    public function testGetQueryWhenQueryExistsInCache(): void
    {
        $segment = $this->getSegment();
        $cacheKey = 'test_query_55_100_42_25';
        $query = $this->getQuery();

        $this->setCacheKeyExpectations();
        $this->cache->expects(self::once())
            ->method('getQuery')
            ->with($cacheKey)
            ->willReturn($query);
        $this->segmentManager->expects(self::never())
            ->method('getEntityQueryBuilder');
        $this->cache->expects(self::never())
            ->method('setQuery');

        self::assertSame($query, $this->segmentProductsQueryProvider->getQuery($segment, 'test_query'));
    }

    public function testGetQueryWhenQueryDoesNotExistInCache(): void
    {
        $segment = $this->getSegment();
        $cacheKey = 'test_query_55_100_42_25';
        $query = $this->getQuery();

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('u.id')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $this->setCacheKeyExpectations();
        $this->cache->expects(self::once())
            ->method('getQuery')
            ->with($cacheKey)
            ->willReturn(null);
        $this->segmentManager->expects(self::once())
            ->method('getEntityQueryBuilder')
            ->with(self::identicalTo($segment))
            ->willReturn($queryBuilder);
        $this->productManager->expects(self::once())
            ->method('restrictQueryBuilder')
            ->with(self::identicalTo($queryBuilder), [])
            ->willReturnArgument(0);
        $this->cache->expects(self::once())
            ->method('setQuery')
            ->with($cacheKey, self::identicalTo($query));


        self::assertSame($query, $this->segmentProductsQueryProvider->getQuery($segment, 'test_query'));
    }

    public function testGetQueryWhenQueryDoesNotExistInCacheAndSegmentManagerCannotBuildQueryForGivenSegment(): void
    {
        $segment = $this->getSegment();
        $cacheKey = 'test_query_55_100_42_25';

        $this->setCacheKeyExpectations();
        $this->cache->expects(self::once())
            ->method('getQuery')
            ->with($cacheKey)
            ->willReturn(null);
        $this->segmentManager->expects(self::once())
            ->method('getEntityQueryBuilder')
            ->with(self::identicalTo($segment))
            ->willReturn(null);
        $this->productManager->expects(self::never())
            ->method('restrictQueryBuilder');
        $this->cache->expects(self::never())
            ->method('setQuery');


        self::assertNull($this->segmentProductsQueryProvider->getQuery($segment, 'test_query'));
    }

    public function testGetQueryWhenSecurityTokenContainsNotSupportedUser(): void
    {
        $segment = $this->getSegment();
        $cacheKey = 'test_query_0_100_42_25';
        $query = $this->getQuery();

        $user = 'test';
        $token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $website = $this->createMock(Website::class);
        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $website->expects(self::once())
            ->method('getId')
            ->willReturn(100);

        $this->cache->expects(self::once())
            ->method('getQuery')
            ->with($cacheKey)
            ->willReturn($query);

        self::assertSame($query, $this->segmentProductsQueryProvider->getQuery($segment, 'test_query'));
    }

    public function testGetQueryWhenSecurityTokenDoesNotContainUser(): void
    {
        $segment = $this->getSegment();
        $cacheKey = 'test_query_0_100_42_25';
        $query = $this->getQuery();

        $token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        $website = $this->createMock(Website::class);
        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $website->expects(self::once())
            ->method('getId')
            ->willReturn(100);

        $this->cache->expects(self::once())
            ->method('getQuery')
            ->with($cacheKey)
            ->willReturn($query);

        self::assertSame($query, $this->segmentProductsQueryProvider->getQuery($segment, 'test_query'));
    }

    public function testGetQueryWhenNoSecurityToken(): void
    {
        $segment = $this->getSegment();
        $cacheKey = 'test_query_0_100_42_25';
        $query = $this->getQuery();

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        $website = $this->createMock(Website::class);
        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $website->expects(self::once())
            ->method('getId')
            ->willReturn(100);

        $this->cache->expects(self::once())
            ->method('getQuery')
            ->with($cacheKey)
            ->willReturn($query);

        self::assertSame($query, $this->segmentProductsQueryProvider->getQuery($segment, 'test_query'));
    }

    public function testGetQueryWhenNoWebsite(): void
    {
        $segment = $this->getSegment();
        $cacheKey = 'test_query_55_0_42_25';
        $query = $this->getQuery();

        $user = $this->createMock(CustomerUser::class);
        $token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $user->expects(self::once())
            ->method('getId')
            ->willReturn(55);

        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn(null);

        $this->cache->expects(self::once())
            ->method('getQuery')
            ->with($cacheKey)
            ->willReturn($query);

        self::assertSame($query, $this->segmentProductsQueryProvider->getQuery($segment, 'test_query'));
    }
}
