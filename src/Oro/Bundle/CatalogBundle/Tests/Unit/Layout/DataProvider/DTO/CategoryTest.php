<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Layout\DataProvider\DTO;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\DTO\Category as CategoryDTO;

class CategoryTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters()
    {
        $category = new Category();
        $category->setMaterializedPath('1_2');

        $subCategory1 = new Category();
        $subCategory1->setMaterializedPath('1_2_3');

        $subCategory2 = new Category();
        $subCategory2->setMaterializedPath('1_2_4');

        $category->addChildCategory($subCategory1)
            ->addChildCategory($subCategory2);

        $subCategory1DTO = new CategoryDTO($subCategory1);
        $dto = new CategoryDTO($category);
        $dto->addChildCategory($subCategory1DTO);

        $this->assertEquals('1_2', $dto->materializedPath());

        $actualSubcategories = $dto->getChildCategories();
        $this->assertEquals(new ArrayCollection([$subCategory1DTO]), $actualSubcategories);
        $this->assertInstanceOf(CategoryDTO::class, $actualSubcategories->first());
    }
}
