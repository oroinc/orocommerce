<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\SegmentProductsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\QueryStub as Query;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SegmentProductsProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const CACHE_KEY = 'segment_products_0_42_1';

    /** @var SegmentManager|\PHPUnit\Framework\MockObject\MockObject */
    private $segmentManager;

    /** @var ProductManager|\PHPUnit\Framework\MockObject\MockObject */
    private $productManager;

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $crypter;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var SegmentProductsProvider */
    private $segmentProductsProvider;

    protected function setUp(): void
    {
        $this->segmentManager = $this->createMock(SegmentManager::class);
        $this->productManager = $this->createMock(ProductManager::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->any())
            ->method('getUser')
            ->willReturn(null);

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $this->cache = $this->createMock(CacheProvider::class);
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);

        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->aclHelper->expects($this->any())
            ->method('apply')
            ->willReturnArgument(0);

        $this->segmentProductsProvider = $this->getMockBuilder(SegmentProductsProvider::class)
            ->setMethods(['getMaxItemsLimit', 'getMinItemsLimit'])
            ->setConstructorArgs(
                [
                    $this->segmentManager,
                    $this->productManager,
                    $registry,
                    $this->tokenStorage,
                    $this->crypter,
                    $this->aclHelper
                ]
            )
            ->getMock();

        $this->segmentProductsProvider->setCache($this->cache, 3600);
    }

    public function testGetProducts(): void
    {
        $queryBuilder = $this->getQueryBuilder();

        $segment = $this->getSegment(42);
        $dql = 'DQL SELECT';
        $qbParameters = new ArrayCollection([new Parameter('parameter', 1)]);
        $hash = $this->getHashData($dql, ['parameter' => 1], ['hint' => 1]);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::CACHE_KEY)
            ->willReturn(null);
        $this->cache->expects($this->once())
            ->method('save')
            ->with(
                self::CACHE_KEY,
                [
                    'dql' => $dql,
                    'parameters' => ['parameter' => 1],
                    'hints' => ['hint' => 1],
                    'hash' => sprintf('encrypt_%s', $hash),
                ],
                3600
            );

        $queryBuilder->expects($this->once())
            ->method('getDQL')
            ->willReturn($dql);
        $queryBuilder->expects($this->once())
            ->method('getParameters')
            ->willReturn($qbParameters);

        $this->crypter->expects($this->any())
            ->method('encryptData')
            ->with($hash)
            ->willReturn(sprintf('encrypt_%s', $hash));

        $result = [new Product()];

        /** @var Query|\PHPUnit\Framework\MockObject\MockObject $query */
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('setMaxResults')
            ->with(4)
            ->willReturnSelf();
        $query->expects($this->once())
            ->method('execute')
            ->with(['parameter' => 1])
            ->willReturn($result);

        $this->em->expects($this->once())
            ->method('createQuery')
            ->with($dql)
            ->willReturn($query);

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->once())
            ->method('getDefaultQueryHints')
            ->willReturn(['hint' => 1]);
        $configuration->expects($this->once())
            ->method('setDefaultQueryHint')
            ->with('hint', 1);

        $this->em->expects($this->atLeastOnce())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $this->assertEquals($result, $this->segmentProductsProvider->getProducts($segment, 1, 4));
    }

    public function testGetProductsWithCache(): void
    {
        $segment = $this->getSegment(42);
        $dql = 'DQL SELECT';
        $hash = $this->getHashData($dql, ['parameter' => 1], ['hint' => 1]);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::CACHE_KEY)
            ->willReturn(
                [
                    'dql' => $dql,
                    'parameters' => ['parameter' => 1],
                    'hints' => ['hint' => 1],
                    'hash' => sprintf('encrypt_%s', $hash)
                ]
            );

        $this->crypter->expects($this->any())
            ->method('encryptData')
            ->with($dql)
            ->willReturn($hash);
        $this->crypter->expects($this->any())
            ->method('decryptData')
            ->with(sprintf('encrypt_%s', $hash))
            ->willReturn($hash);

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->never())
            ->method('getDefaultQueryHints')
            ->willReturn(['hint' => 1]);
        $configuration->expects($this->once())
            ->method('setDefaultQueryHint')
            ->with('hint', 1);

        $this->em->expects($this->atLeastOnce())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $result = [new Product()];

        /** @var Query|\PHPUnit\Framework\MockObject\MockObject $query */
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('setMaxResults')
            ->willReturnSelf();
        $query->expects($this->once())
            ->method('execute')
            ->with(['parameter' => 1])
            ->willReturn($result);

        $this->em->expects($this->once())
            ->method('createQuery')
            ->with($dql)
            ->willReturn($query);

        $this->assertEquals($result, $this->segmentProductsProvider->getProducts($segment, 1, 4));
    }

    public function testGetProductsWithInvalidCache(): void
    {
        $segment = $this->getSegment(42);
        $dql = 'DQL SELECT';
        $invalidDql = 'INVALID DQL SELECT';
        $qbParameters = new ArrayCollection([new Parameter('parameter', 1)]);
        $hash = $this->getHashData($dql, ['parameter' => 1], ['hint' => 1]);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->willReturn(
                [
                    'dql' => $invalidDql,
                    'parameters' => ['parameter' => 1],
                    'hints' => ['hint' => 1],
                    'hash' => sprintf('encrypt_%s', $hash)
                ]
            );

        $this->cache->expects($this->once())
            ->method('save')
            ->with(
                self::CACHE_KEY,
                [
                    'dql' => $dql,
                    'parameters' => ['parameter' => 1],
                    'hints' => ['hint' => 1],
                    'hash' => sprintf('encrypt_%s', $hash),
                ],
                3600
            );

        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->expects($this->once())
            ->method('getDQL')
            ->willReturn($dql);
        $queryBuilder->expects($this->once())
            ->method('getParameters')
            ->willReturn($qbParameters);
        $queryBuilder->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($this->em);

        $this->crypter->expects($this->once())
            ->method('encryptData')
            ->with($hash)
            ->willReturn(sprintf('encrypt_%s', $hash));
        $this->crypter->expects($this->once())
            ->method('decryptData')
            ->with(sprintf('encrypt_%s', $hash))
            ->willReturn($hash);

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->once())
            ->method('getDefaultQueryHints')
            ->willReturn(['hint' => 1]);
        $configuration->expects($this->once())
            ->method('setDefaultQueryHint')
            ->with('hint', 1);

        $this->em->expects($this->atLeastOnce())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $result = [new Product()];

        /** @var Query|\PHPUnit\Framework\MockObject\MockObject $query */
        $query = $this->createMock(Query::class);
        $query->expects($this->once())
            ->method('setMaxResults')
            ->willReturnSelf();
        $query->expects($this->once())
            ->method('execute')
            ->with(['parameter' => 1])
            ->willReturn($result);

        $this->em->expects($this->once())
            ->method('createQuery')
            ->with($dql)
            ->willReturn($query);

        $this->assertEquals($result, $this->segmentProductsProvider->getProducts($segment, 1, 4));
    }

    public function testGetProductsQueryBuilderIsNull(): void
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(self::CACHE_KEY)
            ->willReturn(null);
        $this->cache->expects($this->never())
            ->method('save');

        $this->em->expects($this->never())
            ->method('createQuery');

        $this->assertEquals([], $this->segmentProductsProvider->getProducts($this->getSegment(42), 1, 4));
    }

    /**
     * @return QueryBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);

        $this->segmentManager->expects($this->once())
            ->method('getEntityQueryBuilder')
            ->willReturn($queryBuilder);
        $this->productManager->expects($this->once())
            ->method('restrictQueryBuilder')
            ->with($queryBuilder, [])
            ->willReturn($queryBuilder);

        return $queryBuilder;
    }

    private function getSegment(int $id): Segment
    {
        $segment = $this->getEntity(Segment::class, ['id' => $id]);
        $segment->setRecordsLimit(1);

        return $segment;
    }

    private function getHashData(string $dql, array $parameters, array $queryHints): string
    {
        return md5(serialize([
            'dql' => $dql,
            'parameters' => $parameters,
            'hints' => $queryHints,
        ]));
    }
}
