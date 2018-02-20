<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\AbstractSegmentProductsProvider;
use Oro\Bundle\ProductBundle\Provider\Segment\ProductSegmentProviderInterface;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

abstract class AbstractSegmentProductsProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var SegmentManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $segmentManager;

    /** @var ProductSegmentProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $productSegmentProvider;

    /** @var ProductManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $productManager;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $tokenStorage;

    /** @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $cache;

    /** @var SymmetricCrypterInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $crypter;

    /** @var AbstractSegmentProductsProvider */
    protected $segmentProductsProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->segmentManager = $this->createMock(SegmentManager::class);
        $this->productSegmentProvider = $this->createMock(ProductSegmentProviderInterface::class);
        $this->productManager = $this->createMock(ProductManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(RegistryInterface::class);
        $registry->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->cache = $this->createMock(CacheProvider::class);
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);

        $this->createSegmentProvider($registry);

        $this->segmentProductsProvider->setCache($this->cache, 3600);
    }

    /**
     * @param RegistryInterface $registry
     */
    abstract protected function createSegmentProvider(RegistryInterface $registry);

    /**
     * @return string
     */
    abstract protected function getCacheKey();

    /**
     * @param QueryBuilder|\PHPUnit_Framework_MockObject_MockObject $queryBuilder
     */
    protected function getProducts(QueryBuilder $queryBuilder)
    {
        $result = [new Product()];
        $dql = 'DQL SELECT';
        $hash = 'hash';

        $segment = new Segment();
        $this->productSegmentProvider
            ->expects($this->once())
            ->method('getProductSegmentById')
            ->with(1)
            ->willReturn($segment);

        $this->cache
            ->expects($this->at(0))
            ->method('fetch')
            ->with($this->getCacheKey())
            ->willReturn(null);

        $queryBuilder->expects($this->once())
            ->method('getDQL')
            ->willReturn($dql);

        $parameters = new ArrayCollection([new Parameter('parameter', 1)]);
        $queryBuilder->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);

        $this->cache
            ->expects($this->at(1))
            ->method('fetch')
            ->with('_keyBunch');

        $this->cache
            ->expects($this->at(2))
            ->method('save')
            ->with('_keyBunch', sprintf('["%s"]', $this->getCacheKey()));

        $this->cache
            ->expects($this->at(3))
            ->method('save')
            ->with(
                $this->getCacheKey(),
                ['dql' => $dql, 'parameters' => ['parameter' => 1], 'hash' => md5($hash)],
                3600
            );

        $this->crypter->expects($this->any())
            ->method('encryptData')
            ->with($dql)
            ->willReturn($hash);

        /** @var Query|\PHPUnit_Framework_MockObject_MockObject $query */
        $query = $this->createMock(AbstractQuery::class);
        $this->em
            ->expects($this->once())
            ->method('createQuery')
            ->with($dql)
            ->willReturn($query);

        $query->expects($this->once())
            ->method('execute')
            ->with(['parameter' => 1])
            ->willReturn($result);

        $this->assertEquals($result, $this->segmentProductsProvider->getProducts());
    }

    protected function getProductsWithCache()
    {
        $result = [new Product()];
        $dql = 'DQL SELECT';
        $hash = 'hash';

        $segment = new Segment();
        $this->productSegmentProvider
            ->expects($this->once())
            ->method('getProductSegmentById')
            ->with(1)
            ->willReturn($segment);

        $this->cache
            ->expects($this->once())
            ->method('fetch')
            ->with($this->getCacheKey())
            ->willReturn(['dql' => $dql, 'parameters' => ['parameter' => 1], 'hash' => md5($hash)]);

        $this->crypter->expects($this->any())
            ->method('encryptData')
            ->with($dql)
            ->willReturn($hash);

        /** @var Query|\PHPUnit_Framework_MockObject_MockObject $query */
        $query = $this->createMock(AbstractQuery::class);
        $this->em
            ->expects($this->once())
            ->method('createQuery')
            ->with($dql)
            ->willReturn($query);

        $query->expects($this->once())
            ->method('execute')
            ->with(['parameter' => 1])
            ->willReturn($result);

        $this->assertEquals($result, $this->segmentProductsProvider->getProducts());
    }

    protected function getProductsWithBrokenCache()
    {
        $result = [new Product()];
        $dql = 'DQL SELECT';
        $hash = 'hash';

        $segment = new Segment();
        $this->productSegmentProvider
            ->expects($this->once())
            ->method('getProductSegmentById')
            ->with(1)
            ->willReturn($segment);

        $this->cache
            ->expects($this->once())
            ->method('fetch')
            ->with($this->getCacheKey())
            ->willReturn(['dql' => $dql, 'parameters' => ['parameter' => 1], 'hash' => md5('invalid')]);

        $this->crypter->expects($this->any())
            ->method('encryptData')
            ->with($dql)
            ->willReturn($hash);

        /** @var Query|\PHPUnit_Framework_MockObject_MockObject $query */
        $query = $this->createMock(AbstractQuery::class);
        $this->em
            ->expects($this->once())
            ->method('createQuery')
            ->with($dql)
            ->willReturn($query);

        $query->expects($this->once())
            ->method('execute')
            ->with(['parameter' => 1])
            ->willReturn($result);

        $this->assertEquals($result, $this->segmentProductsProvider->getProducts());
    }

    /**
     * @param QueryBuilder|\PHPUnit_Framework_MockObject_MockObject $queryBuilder
     */
    protected function getProductsWithDisabledCache(QueryBuilder $queryBuilder)
    {
        $result = [new Product()];
        $dql = 'DQL SELECT';

        $this->segmentProductsProvider->disableCache();

        $segment = new Segment();
        $this->productSegmentProvider
            ->expects($this->once())
            ->method('getProductSegmentById')
            ->with(1)
            ->willReturn($segment);

        $this->cache
            ->expects($this->never())
            ->method('fetch');

        $this->cache
            ->expects($this->never())
            ->method('save');

        $queryBuilder->expects($this->once())
            ->method('getDQL')
            ->willReturn($dql);

        $parameters = new ArrayCollection([new Parameter('parameter', 1)]);
        $queryBuilder->expects($this->once())
            ->method('getParameters')
            ->willReturn($parameters);

        /** @var Query|\PHPUnit_Framework_MockObject_MockObject $query */
        $query = $this->createMock(AbstractQuery::class);
        $this->em
            ->expects($this->once())
            ->method('createQuery')
            ->with($dql)
            ->willReturn($query);

        $query->expects($this->once())
            ->method('execute')
            ->with(['parameter' => 1])
            ->willReturn($result);

        $this->assertEquals($result, $this->segmentProductsProvider->getProducts());
    }

    protected function getProductsWithoutSegment()
    {
        $this->productSegmentProvider
            ->expects($this->once())
            ->method('getProductSegmentById')
            ->with(1)
            ->willReturn(null);

        $this->assertEquals([], $this->segmentProductsProvider->getProducts());
    }

    protected function getProductsQueryBuilderIsNull()
    {
        $segment = new Segment();
        $this->productSegmentProvider
            ->expects($this->once())
            ->method('getProductSegmentById')
            ->with(1)
            ->willReturn($segment);

        $this->cache
            ->expects($this->at(0))
            ->method('fetch')
            ->with($this->getCacheKey())
            ->willReturn(null);

        $this->cache
            ->expects($this->never())
            ->method('save');

        $this->em
            ->expects($this->never())
            ->method('createQuery');

        $this->assertEquals([], $this->segmentProductsProvider->getProducts());
    }

    /**
     * @return QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getQueryBuilder()
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->segmentManager->expects($this->once())
            ->method('getEntityQueryBuilder')
            ->willReturn($queryBuilder);
        $this->productManager->expects($this->once())
            ->method('restrictQueryBuilder')
            ->with($queryBuilder, [])
            ->willReturn($queryBuilder);

        return $queryBuilder;
    }
}
