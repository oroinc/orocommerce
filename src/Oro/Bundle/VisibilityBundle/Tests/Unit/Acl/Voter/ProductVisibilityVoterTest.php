<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Acl\Voter;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\VisibilityBundle\Acl\Voter\ProductVisibilityVoter;
use Oro\Bundle\VisibilityBundle\Model\ProductVisibilityQueryBuilderModifier;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProductVisibilityVoterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var ProductVisibilityVoter
     */
    private $voter;

    /**
     * @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cache;

    /**
     * @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $frontendHelper;

    /**
     * @var ProductVisibilityQueryBuilderModifier|\PHPUnit\Framework\MockObject\MockObject
     */
    private $modifier;

    /**
     * @var ResolvedProductVisibilityProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resolvedProductVisibilityProvider;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->voter = new ProductVisibilityVoter($this->doctrineHelper);

        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->voter->setFrontendHelper($this->frontendHelper);

        $this->modifier = $this->createMock(ProductVisibilityQueryBuilderModifier::class);
        $this->voter->setModifier($this->modifier);

        $this->voter->setClassName(Product::class);

        $this->resolvedProductVisibilityProvider = $this->createMock(ResolvedProductVisibilityProvider::class);

        $this->cache = $this->createMock(CacheProvider::class);
    }

    /**
     * @dataProvider unsupportedAttributesDataProvider
     * @param $attributes
     */
    public function testAbstainOnUnsupportedAttribute($attributes)
    {
        $product = new Product();
        $token = $this->createMock(TokenInterface::class);

        $this->assertEquals(
            ProductVisibilityVoter::ACCESS_ABSTAIN,
            $this->voter->vote($token, $product, $attributes)
        );
    }

    /**
     * @return array
     */
    public function unsupportedAttributesDataProvider()
    {
        return [
            [['EDIT']],
            [['DELETE']],
            [['CREATE']],
            [['ASSIGN']],
        ];
    }

    /**
     * @dataProvider permissionsWithoutCacheDataProvider
     * @param array|null $queryResult
     * @param int $expected
     */
    public function testPermissionsWithoutCache($queryResult, $expected)
    {
        $productId = 42;
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => $productId]);

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->assertQueryExecute($product, $queryResult);

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            $expected,
            $this->voter->vote($token, $product, ['VIEW'])
        );
    }

    /**
     * @dataProvider permissionsWithoutCacheDataProvider
     * @param array|null $queryResult
     * @param int $expected
     */
    public function testPermissionsEmptyCache($queryResult, $expected)
    {
        $this->voter->setAttributePermissionCache($this->cache);
        
        $productId = 42;
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => $productId]);

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->cache->expects($this->once())
            ->method('contains')
            ->willReturn(false);

        $this->assertQueryExecute($product, $queryResult);

        $this->cache->expects($this->once())
            ->method('save')
            ->with('Oro\Bundle\ProductBundle\Entity\Product_42', !empty($queryResult));

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            $expected,
            $this->voter->vote($token, $product, ['VIEW'])
        );
    }

    /**
     * @return array
     */
    public function permissionsWithoutCacheDataProvider()
    {
        return [
            'access granted' => [
                'queryResult' => [1 => '1'],
                'expected' =>  ProductVisibilityVoter::ACCESS_GRANTED
            ],
            'access denied' => [
                'queryResult' => null,
                'expected' =>  ProductVisibilityVoter::ACCESS_DENIED
            ]
        ];
    }

    /**
     * @dataProvider permissionsWithCacheDataProvider
     * @param boolean $cacheResult
     * @param int $expected
     */
    public function testPermissionsFromCache($cacheResult, $expected)
    {
        $this->voter->setAttributePermissionCache($this->cache);
        
        $productId = 42;
        $product = $this->getEntity(Product::class, ['id' => $productId]);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($product, false)
            ->willReturn($productId);

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->cache->expects($this->once())
            ->method('contains')
            ->willReturn(true);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('Oro\Bundle\ProductBundle\Entity\Product_42')
            ->willReturn($cacheResult);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepository')
            ->with(Product::class);

        $this->cache->expects($this->never())
            ->method('save');

        /** @var TokenInterface $token */
        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            $expected,
            $this->voter->vote($token, $product, ['VIEW'])
        );
    }

    /**
     * @return array
     */
    public function permissionsWithCacheDataProvider()
    {
        return [
            'access granted' => [
                'cacheResult' => true,
                'expected' =>  ProductVisibilityVoter::ACCESS_GRANTED
            ],
            'access denied' => [
                'cacheResult' => false,
                'expected' =>  ProductVisibilityVoter::ACCESS_DENIED
            ]
        ];
    }

    /**
     * @param Product $product
     * @param array|null $queryResult
     */
    protected function assertQueryExecute(Product $product, $queryResult)
    {
        $qb = $this->createMock(QueryBuilder::class);

        $repository = $this->createMock(ProductRepository::class);
        $repository->expects($this->once())
            ->method('getProductsQueryBuilder')
            ->with([$product->getId()])
            ->willReturn($qb);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(Product::class)
            ->willReturn($repository);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($product, false)
            ->willReturn($product->getId());

        $this->modifier->expects($this->once())
            ->method('modify')
            ->with($qb);

        $query = $this->createMock(AbstractQuery::class);

        $query->expects($this->once())
            ->method('getScalarResult')
            ->willReturn($queryResult);

        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $qb->expects($this->once())
            ->method('resetDQLPart')
            ->with('select')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('select')
            ->with('1')
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with('1')
            ->willReturn($qb);
    }

    public function testVoteWhenNotFrontend(): void
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->resolvedProductVisibilityProvider
            ->expects($this->never())
            ->method('isVisible');

        $token = $this->createMock(TokenInterface::class);
        $product = $this->createMock(Product::class);
        $this->assertEquals(ProductVisibilityVoter::ACCESS_ABSTAIN, $this->voter->vote($token, $product, ['VIEW']));
    }

    /**
     * @dataProvider voteDataProvider
     *
     * @param bool $isVisible
     * @param int $expected
     */
    public function testVoteWhenVisibilityProvider(bool $isVisible, int $expected): void
    {
        $productId = 42;
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => $productId]);

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($product, false)
            ->willReturn($productId);

        $this->resolvedProductVisibilityProvider
            ->expects($this->once())
            ->method('isVisible')
            ->with($productId)
            ->willReturn($isVisible);

        $token = $this->createMock(TokenInterface::class);
        $this->voter->setResolvedProductVisibilityProvider($this->resolvedProductVisibilityProvider);
        $this->assertEquals($expected, $this->voter->vote($token, $product, ['VIEW']));
    }

    /**
     * @return array
     */
    public function voteDataProvider(): array
    {
        return [
            'access granted' => [
                'isVisible' => true,
                'expected' => ProductVisibilityVoter::ACCESS_GRANTED,
            ],
            'access denied' => [
                'isVisible' => false,
                'expected' => ProductVisibilityVoter::ACCESS_DENIED,
            ],
        ];
    }
}
