<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\CatalogBundle\Entity\Category;

class CategoryVisibilityTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $entity = new CategoryVisibility();
        $category = new Category();
        $this->assertPropertyAccessors(
            $entity,
            [
                ['id', 1],
                ['category', $category],
                ['visibility', CategoryVisibility::PARENT_CATEGORY],
            ]
        );
        $entity->setTargetEntity($category);
        $this->assertEquals($entity->getTargetEntity(), $category);
        $this->assertEquals(CategoryVisibility::CONFIG, $entity->getDefault($category));

        $entity->setCategory((new Category())->setParentCategory(new Category()));
        $this->assertEquals(
            CategoryVisibility::PARENT_CATEGORY,
            CategoryVisibility::getDefault($entity->getCategory())
        );
        $visibilityList = CategoryVisibility::getVisibilityList($category);
        $this->assertInternalType('array', $visibilityList);
        $this->assertNotEmpty($visibilityList);
    }
}
