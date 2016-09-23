<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Fallback\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\EntityBundle\Exception\Fallback\InvalidFallbackArgumentException;
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
     * @var CategoryFallbackProvider
     */
    protected $categoryFallbackProvider;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryFallbackProvider = new CategoryFallbackProvider($this->doctrineHelper);
    }

    public function testIsFallbackSupportedReturnsTrue()
    {
        $this->assertTrue($this->categoryFallbackProvider->isFallbackSupported(new \stdClass(), 'test'));
    }

    public function testGetFallbackHolderEntityThrowsExceptionIfNotProduct()
    {
        $this->setExpectedException(InvalidFallbackArgumentException::class);
        $this->categoryFallbackProvider->getFallbackHolderEntity(new \stdClass(), 'test');
    }

    public function testGetFallbackHolderEntityGetsCategory()
    {
        $product = new Product();
        $category = new Category();
        $repo = $this->getMockBuilder(CategoryRepository::class)->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repo);
        $repo->expects($this->once())->method('findOneByProduct')->willReturn($category);

        $result = $this->categoryFallbackProvider->getFallbackHolderEntity($product, 'test');
        $this->assertSame($category, $result);
    }
}
