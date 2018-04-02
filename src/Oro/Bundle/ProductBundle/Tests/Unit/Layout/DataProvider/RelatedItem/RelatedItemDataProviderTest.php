<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider\RelatedItem;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\Restriction\RestrictedProductRepository;
use Oro\Bundle\ProductBundle\Layout\DataProvider\RelatedItem\RelatedItemDataProvider;
use Oro\Bundle\ProductBundle\RelatedItem\FinderStrategyInterface;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct\RelatedProductsConfigProvider;
use Oro\Bundle\UIBundle\Tests\Unit\Provider\FakeUserAgentProvider;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RelatedItemDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var RestrictedProductRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $restrictedRepository;

    /** @var RelatedProductsConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $configProvider;

    /** @var FinderStrategyInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $finder;

    /** @var FakeUserAgentProvider */
    private $userAgentProvider;

    /** @var RelatedItemDataProvider */
    private $dataProvider;

    public function setUp()
    {
        $this->finder = $this->getMockBuilder(FinderStrategyInterface::class)->getMock();
        $this->configProvider = $this->getMockBuilder(RelatedProductsConfigProvider::class)
            ->disableOriginalConstructor()->getMock();

        $this->restrictedRepository = $this->getMockBuilder(RestrictedProductRepository::class)
            ->disableOriginalConstructor()->getMock();

        $this->userAgentProvider = new FakeUserAgentProvider();

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
            $this->getEntity(Product::class, ['id' => 2]),
            $this->getEntity(Product::class, ['id' => 3]),
        ]));

        $this->assertEmpty($this->dataProvider->getRelatedItems(new Product()));
    }

    public function testReturnRelatedProducts()
    {
        $relatedProducts = new ArrayCollection([
            $this->getEntity(Product::class, ['id' => 2]),
            $this->getEntity(Product::class, ['id' => 3]),
        ]);

        $this->finderReturnsRelatedProducts($relatedProducts);
        $this->minimumRelatedProductsIs(2);
        $this->restrictionReturnsRelatedProducts($relatedProducts);

        $this->assertEquals(
            $relatedProducts,
            $this->dataProvider->getRelatedItems(new Product())
        );
    }

    public function testDoesNotReturnProductsIfThereAreLessRelatedProductThanSpecifiedInMinConfiguration()
    {
        $relatedProducts = new ArrayCollection([
            $this->getEntity(Product::class, ['id' => 2]),
            $this->getEntity(Product::class, ['id' => 3]),
        ]);

        $this->finderReturnsRelatedProducts($relatedProducts);
        $this->minimumRelatedProductsIs(3);

        $this->assertEquals([], $this->dataProvider->getRelatedItems(new Product()));
    }

    public function testReturnRestrictedRelatedProducts()
    {
        $product2 = $this->getEntity(Product::class, ['id' => 2]);
        $product3 = $this->getEntity(Product::class, ['id' => 3]);
        $product4 = $this->getEntity(Product::class, ['id' => 4]);
        $relatedProducts = new ArrayCollection([$product2, $product3, $product4]);
        $restrictedProducts = new ArrayCollection([$product2, $product3]);

        $this->finderReturnsRelatedProducts($relatedProducts);
        $this->minimumRelatedProductsIs(2);
        $this->restrictionReturnsRelatedProducts($restrictedProducts);

        $this->assertEquals(
            $restrictedProducts,
            $this->dataProvider->getRelatedItems(new Product())
        );
    }

    public function testReturnNoMoreRestrictedRelatedProductsThanSpecifiedInMaximumItemsConfiguration()
    {
        $product2 = $this->getEntity(Product::class, ['id' => 2]);
        $product3 = $this->getEntity(Product::class, ['id' => 3]);
        $product4 = $this->getEntity(Product::class, ['id' => 4]);
        $relatedProducts = new ArrayCollection([$product2, $product3, $product4]);

        $this->finderReturnsRelatedProducts($relatedProducts);
        $this->restrictionReturnsRelatedProducts($relatedProducts, 1);

        $this->assertEquals(
            [$product2],
            $this->dataProvider->getRelatedItems(new Product())
        );
    }

    public function testDoesNotReturnRestrictedProductsIfThereAreLessRelatedProductThanSpecifiedInMinConfiguration()
    {
        $product2 = $this->getEntity(Product::class, ['id' => 2]);
        $product3 = $this->getEntity(Product::class, ['id' => 3]);
        $product4 = $this->getEntity(Product::class, ['id' => 4]);
        $relatedProducts = new ArrayCollection([$product2, $product3, $product4]);
        $restrictedProducts = new ArrayCollection([$product2, $product3]);

        $this->finderReturnsRelatedProducts($relatedProducts);
        $this->minimumRelatedProductsIs(3);
        $this->restrictionReturnsRelatedProducts($restrictedProducts);

        $this->assertEquals([], $this->dataProvider->getRelatedItems(new Product()));
    }

    public function testSliderEnabledOnDesktop()
    {
        $this->userAgentProvider->isDesktop = true;
        $this->assertTrue($this->dataProvider->isSliderEnabled());
    }

    public function testSliderEnabledOnMobileWhenConfigEnabled()
    {
        $this->userAgentProvider->isDesktop = false;
        $this->isSliderEnabledOnMobile(true);
        $this->assertTrue($this->dataProvider->isSliderEnabled());
    }

    public function testSliderDisabledOnMobileWhenConfigIsDisabled()
    {
        $this->userAgentProvider->isDesktop = false;
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

    /**
     * @param Product[]|ArrayCollection $relatedProducts
     */
    private function finderReturnsRelatedProducts($relatedProducts)
    {
        $this->finder->expects($this->once())
            ->method('find')
            ->willReturn($relatedProducts);
    }

    /**
     * @param int $count
     */
    private function minimumRelatedProductsIs($count)
    {
        $this->configProvider->expects($this->any())
            ->method('getMinimumItems')
            ->willReturn($count);
    }

    private function isSliderEnabledOnMobile($isEnabled)
    {
        $this->configProvider->expects($this->any())
            ->method('isSliderEnabledOnMobile')
            ->willReturn($isEnabled);
    }

    private function isAddButtonVisible($isVisible)
    {
        $this->configProvider->expects($this->any())
            ->method('isAddButtonVisible')
            ->willReturn($isVisible);
    }

    /**
     * @param Product[]|ArrayCollection $restrictedProducts
     * @param null|int                      $max
     */
    private function restrictionReturnsRelatedProducts($restrictedProducts, $max = null)
    {
        if ($max) {
            $restrictedProducts = $restrictedProducts->slice(0, $max);
        }

        $this->restrictedRepository->expects($this->any())
            ->method('findProducts')
            ->willReturn($restrictedProducts);
    }
}
