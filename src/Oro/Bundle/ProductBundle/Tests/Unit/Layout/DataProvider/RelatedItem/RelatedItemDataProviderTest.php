<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider\RelatedItem;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\Restriction\RestrictedProductRepository;
use Oro\Bundle\ProductBundle\Layout\DataProvider\RelatedItem\RelatedItemDataProvider;
use Oro\Bundle\ProductBundle\RelatedItem\FinderStrategyInterface;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct\RelatedProductsConfigProvider;
use Oro\Bundle\UIBundle\Provider\UserAgentInterface;
use Oro\Bundle\UIBundle\Provider\UserAgentProviderInterface;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RelatedItemDataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var RestrictedProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $restrictedRepository;

    /** @var RelatedProductsConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var FinderStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $finder;

    /** @var UserAgentProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $userAgentProvider;

    /** @var RelatedItemDataProvider */
    private $dataProvider;

    protected function setUp(): void
    {
        $this->finder = $this->createMock(FinderStrategyInterface::class);
        $this->configProvider = $this->createMock(RelatedProductsConfigProvider::class);
        $this->restrictedRepository = $this->createMock(RestrictedProductRepository::class);
        $this->userAgentProvider = $this->createMock(UserAgentProviderInterface::class);

        $this->dataProvider = new RelatedItemDataProvider(
            $this->finder,
            $this->configProvider,
            $this->restrictedRepository,
            $this->userAgentProvider
        );
    }

    public function testDoesNotReturnRelatedProductsIfFinderDoesNotFindAny()
    {
        $this->finderReturnsRelatedProducts([]);
        $this->restrictionReturnsRelatedProducts(new ArrayCollection([
            $this->getProduct(2),
            $this->getProduct(3),
        ]));

        $this->assertEmpty($this->dataProvider->getRelatedItems(new Product()));
    }

    public function testReturnRelatedProducts()
    {
        $relatedProducts = new ArrayCollection([
            $this->getProduct(2),
            $this->getProduct(3),
        ]);

        $this->finderReturnsRelatedProducts([2, 3]);
        $this->minimumRelatedProductsIs(2);
        $this->restrictionReturnsRelatedProducts($relatedProducts);

        $this->assertEquals(
            $relatedProducts,
            $this->dataProvider->getRelatedItems(new Product())
        );
    }

    public function testDoesNotReturnProductsIfThereAreLessRelatedProductThanSpecifiedInMinConfiguration()
    {
        $this->finderReturnsRelatedProducts([2, 3]);
        $this->minimumRelatedProductsIs(3);

        $this->assertEquals([], $this->dataProvider->getRelatedItems(new Product()));
    }

    public function testReturnRestrictedRelatedProducts()
    {
        $product2 = $this->getProduct(2);
        $product3 = $this->getProduct(3);

        $restrictedProducts = new ArrayCollection([$product2, $product3]);

        $this->finderReturnsRelatedProducts([2, 3, 4]);
        $this->minimumRelatedProductsIs(2);
        $this->restrictionReturnsRelatedProducts($restrictedProducts);

        $this->assertEquals(
            $restrictedProducts,
            $this->dataProvider->getRelatedItems(new Product())
        );
    }

    public function testReturnNoMoreRestrictedRelatedProductsThanSpecifiedInMaximumItemsConfiguration()
    {
        $product2 = $this->getProduct(2);
        $product3 = $this->getProduct(3);
        $product4 = $this->getProduct(4);
        $relatedProducts = new ArrayCollection([$product2, $product3, $product4]);

        $this->finderReturnsRelatedProducts([2, 3, 4]);
        $this->restrictionReturnsRelatedProducts($relatedProducts, 1);

        $this->assertEquals(
            [$product2],
            $this->dataProvider->getRelatedItems(new Product())
        );
    }

    public function testDoesNotReturnRestrictedProductsIfThereAreLessRelatedProductThanSpecifiedInMinConfiguration()
    {
        $product2 = $this->getProduct(2);
        $product3 = $this->getProduct(3);

        $restrictedProducts = new ArrayCollection([$product2, $product3]);

        $this->finderReturnsRelatedProducts([2, 3, 4]);
        $this->minimumRelatedProductsIs(3);
        $this->restrictionReturnsRelatedProducts($restrictedProducts);

        $this->assertEquals([], $this->dataProvider->getRelatedItems(new Product()));
    }

    public function testSliderEnabledOnDesktop()
    {
        $this->isUserAgentOnMobile(false);
        $this->assertTrue($this->dataProvider->isSliderEnabled());
    }

    public function testSliderEnabledOnMobileWhenConfigEnabled()
    {
        $this->isUserAgentOnMobile(true);
        $this->isSliderEnabledOnMobile(true);
        $this->assertTrue($this->dataProvider->isSliderEnabled());
    }

    public function testSliderDisabledOnMobileWhenConfigIsDisabled()
    {
        $this->isUserAgentOnMobile(true);
        $this->assertFalse($this->dataProvider->isSliderEnabled());
    }

    public function testAddButtonIsVisibleWhenConfigIsEnabled()
    {
        $this->isAddButtonVisible(true);
        $this->assertTrue($this->dataProvider->isAddButtonVisible());
    }

    public function testAddButtonIsNotVisibleWhenConfigIsDisabled()
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

    private function finderReturnsRelatedProducts(array $relatedProductIds): void
    {
        $this->finder->expects($this->once())
            ->method('findIds')
            ->willReturn($relatedProductIds);
    }

    private function minimumRelatedProductsIs(int $count): void
    {
        $this->configProvider->expects($this->any())
            ->method('getMinimumItems')
            ->willReturn($count);
    }

    private function isUserAgentOnMobile(bool $isMobile): void
    {
        $userAgent = $this->createMock(UserAgentInterface::class);
        $this->userAgentProvider->expects($this->once())
            ->method('getUserAgent')
            ->willReturn($userAgent);
        $userAgent->expects($this->once())
            ->method('isMobile')
            ->willReturn($isMobile);
    }

    private function isSliderEnabledOnMobile(bool $isEnabled): void
    {
        $this->configProvider->expects($this->any())
            ->method('isSliderEnabledOnMobile')
            ->willReturn($isEnabled);
    }

    private function isAddButtonVisible(bool $isVisible): void
    {
        $this->configProvider->expects($this->any())
            ->method('isAddButtonVisible')
            ->willReturn($isVisible);
    }

    private function restrictionReturnsRelatedProducts(ArrayCollection $restrictedProducts, int $max = null): void
    {
        if ($max) {
            $restrictedProducts = $restrictedProducts->slice(0, $max);
        }

        $this->restrictedRepository->expects($this->any())
            ->method('findProducts')
            ->willReturn($restrictedProducts);
    }
}
