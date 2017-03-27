<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Fallback\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\EntityBundle\Exception\Fallback\InvalidFallbackArgumentException;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;

class CategoryFallbackProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var SystemConfigFallbackProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $systemConfigFallbackProvider;

    /**
     * @var CategoryFallbackProvider
     */
    protected $categoryFallbackProvider;

    protected function setUp()
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
        $repo = $this->getMockBuilder(CategoryRepository::class)->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repo);
        $this->systemConfigFallbackProvider->expects($this->never())->method('getFallbackHolderEntity');
        $repo->expects($this->once())->method('findOneByProduct')->willReturn($category);

        $result = $this->categoryFallbackProvider->getFallbackHolderEntity($product, 'test');
        $this->assertSame($category, $result);
    }

    public function testGetFallbackHolderEntityWithoutCategory()
    {
        $product = new Product();
        $repo = $this->getMockBuilder(CategoryRepository::class)->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repo);
        $repo->expects($this->once())->method('findOneByProduct')->willReturn(null);
        $this->systemConfigFallbackProvider->expects($this->once())->method('getFallbackHolderEntity')->willReturn(123);

        $result = $this->categoryFallbackProvider->getFallbackHolderEntity($product, 'test');
        $this->assertSame(123, $result);
    }
}
