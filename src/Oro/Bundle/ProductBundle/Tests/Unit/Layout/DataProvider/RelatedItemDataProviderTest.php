<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Layout\DataProvider\RelatedItemDataProvider;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Provider\ProductListBuilder;
use Oro\Bundle\ProductBundle\RelatedItem\FinderStrategyInterface;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedItemConfigProviderInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UIBundle\Provider\UserAgentInterface;
use Oro\Bundle\UIBundle\Provider\UserAgentProviderInterface;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RelatedItemDataProviderTest extends \PHPUnit\Framework\TestCase
{
    private const PRODUCT_LIST_TYPE = 'test_list';

    /** @var FinderStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $finder;

    /** @var RelatedItemConfigProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var UserAgentProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $userAgentProvider;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ProductManager|\PHPUnit\Framework\MockObject\MockObject */
    private $productManager;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var ProductListBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $productListBuilder;

    /** @var RelatedItemDataProvider */
    private $dataProvider;

    protected function setUp(): void
    {
        $this->finder = $this->createMock(FinderStrategyInterface::class);
        $this->configProvider = $this->createMock(RelatedItemConfigProviderInterface::class);
        $this->userAgentProvider = $this->createMock(UserAgentProviderInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->productManager = $this->createMock(ProductManager::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->productListBuilder = $this->createMock(ProductListBuilder::class);

        $this->configProvider->expects(self::any())
            ->method('isBidirectional')
            ->willReturn(false);

        $this->dataProvider = new RelatedItemDataProvider(
            $this->finder,
            $this->configProvider,
            $this->userAgentProvider,
            $this->doctrine,
            $this->productManager,
            $this->aclHelper,
            $this->productListBuilder,
            self::PRODUCT_LIST_TYPE
        );
    }

    public function testDoesNotReturnRelatedProductsIfFinderDoesNotFindAny(): void
    {
        $this->finderReturnsRelatedProducts([]);
        $this->doctrine->expects(self::never())
            ->method('getRepository');
        $this->productListBuilder->expects(self::never())
            ->method('getProductsByIds');

        self::assertSame([], $this->dataProvider->getRelatedItems($this->getProduct(100)));
        // test memory cache
        self::assertSame([], $this->dataProvider->getRelatedItems($this->getProduct(100)));
    }

    public function testReturnRelatedProducts(): void
    {
        $product2 = $this->getProduct(2);
        $product3 = $this->getProduct(3);

        $relatedProducts = [$product2, $product3];

        $productViews = [
            $this->getProductView($product2->getId()),
            $this->getProductView($product3->getId())
        ];

        $this->finderReturnsRelatedProducts([2, 3]);
        $this->minimumRelatedProductsIs(2);
        $this->maximumRelatedProductsIs(5);
        $productIds = $this->restrictionReturnsRelatedProducts($relatedProducts, null, 5);
        $this->productListBuilder->expects(self::once())
            ->method('getProductsByIds')
            ->with(self::PRODUCT_LIST_TYPE, $productIds)
            ->willReturn($productViews);

        self::assertSame([$product2->getId(), $product3->getId()], $productIds);
        self::assertEquals($productViews, $this->dataProvider->getRelatedItems($this->getProduct(100)));
        // test memory cache
        self::assertEquals($productViews, $this->dataProvider->getRelatedItems($this->getProduct(100)));
    }

    public function testDoesNotReturnProductsIfThereAreLessRelatedProductThanSpecifiedInMinConfiguration(): void
    {
        $this->finderReturnsRelatedProducts([2, 3]);
        $this->minimumRelatedProductsIs(3);

        self::assertSame([], $this->dataProvider->getRelatedItems($this->getProduct(100)));
        // test memory cache
        self::assertSame([], $this->dataProvider->getRelatedItems($this->getProduct(100)));
    }

    public function testReturnRestrictedRelatedProducts(): void
    {
        $product2 = $this->getProduct(2);
        $product3 = $this->getProduct(3);

        $restrictedProducts = [$product2, $product3];

        $productViews = [
            $this->getProductView($product2->getId()),
            $this->getProductView($product3->getId())
        ];

        $this->finderReturnsRelatedProducts([2, 3, 4]);
        $this->minimumRelatedProductsIs(2);
        $productIds = $this->restrictionReturnsRelatedProducts($restrictedProducts);
        $this->productListBuilder->expects(self::once())
            ->method('getProductsByIds')
            ->with(self::PRODUCT_LIST_TYPE, $productIds)
            ->willReturn($productViews);

        self::assertSame([$product2->getId(), $product3->getId()], $productIds);
        self::assertEquals($productViews, $this->dataProvider->getRelatedItems($this->getProduct(100)));
        // test memory cache
        self::assertEquals($productViews, $this->dataProvider->getRelatedItems($this->getProduct(100)));
    }

    public function testReturnNoMoreRestrictedRelatedProductsThanSpecifiedInMaximumItemsConfiguration(): void
    {
        $product2 = $this->getProduct(2);
        $product3 = $this->getProduct(3);
        $product4 = $this->getProduct(4);

        $relatedProducts = [$product2, $product3, $product4];

        $productViews = [$this->getProductView($product2->getId())];

        $this->finderReturnsRelatedProducts([2, 3, 4]);
        $productIds = $this->restrictionReturnsRelatedProducts($relatedProducts, 1);
        $this->productListBuilder->expects(self::once())
            ->method('getProductsByIds')
            ->with(self::PRODUCT_LIST_TYPE, $productIds)
            ->willReturn($productViews);

        self::assertSame([$product2->getId()], $productIds);
        self::assertEquals($productViews, $this->dataProvider->getRelatedItems($this->getProduct(100)));
        // test memory cache
        self::assertEquals($productViews, $this->dataProvider->getRelatedItems($this->getProduct(100)));
    }

    public function testDoesNotReturnRestrictedProductsIfThereAreLessRelatedProductThanSetInMinConfiguration(): void
    {
        $product2 = $this->getProduct(2);
        $product3 = $this->getProduct(3);

        $restrictedProducts = [$product2, $product3];

        $this->finderReturnsRelatedProducts([2, 3, 4]);
        $this->minimumRelatedProductsIs(3);
        $this->restrictionReturnsRelatedProducts($restrictedProducts);

        self::assertSame([], $this->dataProvider->getRelatedItems($this->getProduct(100)));
        // test memory cache
        self::assertSame([], $this->dataProvider->getRelatedItems($this->getProduct(100)));
    }

    public function testSliderEnabledOnDesktop(): void
    {
        $this->isUserAgentOnMobile(false);
        $this->assertTrue($this->dataProvider->isSliderEnabled());
    }

    public function testSliderEnabledOnMobileWhenConfigEnabled(): void
    {
        $this->isUserAgentOnMobile(true);
        $this->isSliderEnabledOnMobile(true);
        $this->assertTrue($this->dataProvider->isSliderEnabled());
    }

    public function testSliderDisabledOnMobileWhenConfigIsDisabled(): void
    {
        $this->isUserAgentOnMobile(true);
        $this->isSliderEnabledOnMobile(false);
        $this->assertFalse($this->dataProvider->isSliderEnabled());
    }

    public function testAddButtonIsVisibleWhenConfigIsEnabled(): void
    {
        $this->isAddButtonVisible(true);
        $this->assertTrue($this->dataProvider->isAddButtonVisible());
    }

    public function testAddButtonIsNotVisibleWhenConfigIsDisabled(): void
    {
        $this->isAddButtonVisible(false);
        $this->assertFalse($this->dataProvider->isAddButtonVisible());
    }

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
    }

    private function getProductView(int $id): ProductView
    {
        $productView = new ProductView();
        $productView->set('id', $id);

        return $productView;
    }

    private function finderReturnsRelatedProducts(array $relatedProductIds): void
    {
        $this->finder->expects(self::once())
            ->method('findIds')
            ->willReturn($relatedProductIds);
    }

    private function minimumRelatedProductsIs(int $limit): void
    {
        $this->configProvider->expects(self::atLeastOnce())
            ->method('getMinimumItems')
            ->willReturn($limit);
    }

    private function maximumRelatedProductsIs(int $limit): void
    {
        $this->configProvider->expects(self::atLeastOnce())
            ->method('getMaximumItems')
            ->willReturn($limit);
    }

    private function isUserAgentOnMobile(bool $isMobile): void
    {
        $userAgent = $this->createMock(UserAgentInterface::class);
        $this->userAgentProvider->expects(self::once())
            ->method('getUserAgent')
            ->willReturn($userAgent);
        $userAgent->expects(self::once())
            ->method('isMobile')
            ->willReturn($isMobile);
    }

    private function isSliderEnabledOnMobile(bool $isEnabled): void
    {
        $this->configProvider->expects(self::any())
            ->method('isSliderEnabledOnMobile')
            ->willReturn($isEnabled);
    }

    private function isAddButtonVisible(bool $isVisible): void
    {
        $this->configProvider->expects(self::any())
            ->method('isAddButtonVisible')
            ->willReturn($isVisible);
    }

    /**
     * @param Product[] $products
     * @param int|null  $max
     * @param int|null  $limit
     *
     * @return int[]
     */
    private function restrictionReturnsRelatedProducts(array $products, int $max = null, int $limit = null): array
    {
        if ($max) {
            $products = array_slice($products, 0, $max, true);
        }

        $rows = [];
        foreach ($products as $product) {
            $rows[] = ['id' => $product->getId()];
        }

        $productRepository = $this->createMock(ProductRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($productRepository);

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $productRepository->expects(self::once())
            ->method('getProductsQueryBuilder')
            ->willReturn($qb);
        $qb->expects(self::once())
            ->method('select')
            ->with('p.id')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('orderBy')
            ->with('p.id')
            ->willReturnSelf();
        if ($limit) {
            $qb->expects(self::once())
                ->method('setMaxResults')
                ->with($limit)
                ->willReturnSelf();
        } else {
            $qb->expects(self::never())
                ->method('setMaxResults');
        }

        $this->productManager->expects(self::once())
            ->method('restrictQueryBuilder')
            ->with(self::identicalTo($qb), [])
            ->willReturn($qb);
        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with(self::identicalTo($qb))
            ->willReturn($query);

        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn($rows);

        return array_column($rows, 'id');
    }
}
