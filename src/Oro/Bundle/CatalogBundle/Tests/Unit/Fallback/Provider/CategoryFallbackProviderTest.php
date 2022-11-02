<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Fallback\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Exception\Fallback\InvalidFallbackArgumentException;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;

class CategoryFallbackProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var SystemConfigFallbackProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $systemConfigFallbackProvider;

    /**
     * @var CategoryFallbackProvider
     */
    protected $categoryFallbackProvider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->systemConfigFallbackProvider = $this->createMock(SystemConfigFallbackProvider::class);

        $this->categoryFallbackProvider = new CategoryFallbackProvider(
            $this->doctrineHelper,
            $this->systemConfigFallbackProvider
        );
    }

    public function testIsFallbackSupportedReturnsCorrectValue()
    {
        $this->assertFalse($this->categoryFallbackProvider->isFallbackSupported(new \stdClass(), 'test'));
        $this->assertTrue($this->categoryFallbackProvider->isFallbackSupported(new Product(), 'test'));
    }

    public function testGetFallbackHolderEntityThrowsExceptionIfNotProduct()
    {
        $this->expectException(InvalidFallbackArgumentException::class);
        $this->categoryFallbackProvider->getFallbackHolderEntity(new \stdClass(), 'test');
    }

    public function testGetFallbackHolderEntityWithCategory()
    {
        $product = new Product();
        $category = new Category();
        $product->setCategory($category);
        $this->systemConfigFallbackProvider->expects($this->never())->method('getFallbackHolderEntity');

        $result = $this->categoryFallbackProvider->getFallbackHolderEntity($product, 'test');
        $this->assertSame($category, $result);
    }

    public function testGetFallbackHolderEntityWithoutCategory()
    {
        $product = new Product();
        $this->systemConfigFallbackProvider->expects($this->once())->method('getFallbackHolderEntity')->willReturn(123);

        $result = $this->categoryFallbackProvider->getFallbackHolderEntity($product, 'test');
        $this->assertSame(123, $result);
    }

    public function testGetFallbackEntityClass()
    {
        $this->assertSame(Category::class, $this->categoryFallbackProvider->getFallbackEntityClass());
    }
}
