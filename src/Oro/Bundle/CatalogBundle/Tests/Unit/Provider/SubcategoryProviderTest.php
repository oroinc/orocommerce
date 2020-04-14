<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CatalogBundle\Provider\SubcategoryProvider;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

class SubcategoryProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var CategoryTreeProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $categoryTreeProvider;

    /** @var SubcategoryProvider */
    protected $provider;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->categoryTreeProvider = $this->createMock(CategoryTreeProvider::class);

        $this->provider = new SubcategoryProvider($this->tokenAccessor, $this->categoryTreeProvider);
    }

    public function testGetAvailableSubcategories()
    {
        $currentCategory = new Category();
        $user = new User();

        $category1 = $this->getCategory($currentCategory); // without products
        $category2 = $this->getCategory($currentCategory, [new Product()]);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->categoryTreeProvider->expects($this->once())
            ->method('getCategories')
            ->with($user, $currentCategory, false)
            ->willReturn([$category1, $category2]);

        $this->assertEquals(
            [
                $category1,
                $category2
            ],
            $this->provider->getAvailableSubcategories($currentCategory)
        );
    }

    public function testGetAvailableSubcategoriesWithoutCategory()
    {
        $this->categoryTreeProvider->expects($this->never())
            ->method($this->anything());

        $this->assertEquals([], $this->provider->getAvailableSubcategories());
    }

    /**
     * @param Category $parentCategory
     * @param array $products
     * @return Category
     */
    protected function getCategory(Category $parentCategory = null, array $products = [])
    {
        $category = new Category();
        $category->setParentCategory($parentCategory);
        $category->setProducts(new ArrayCollection($products));

        return $category;
    }
}
