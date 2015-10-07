<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryVisibilityTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $entity = new CategoryVisibility();

        $this->assertPropertyAccessors(
            $entity,
            [
                ['id', 1],
                ['category', new Category()],
                ['visibility', CategoryVisibility::PARENT_CATEGORY],
            ]
        );
        $this->assertEquals(
            CategoryVisibility::PARENT_CATEGORY,
            CategoryVisibility::getDefault($entity->getCategory())
        );

        $visibilityList = CategoryVisibility::getVisibilityList($entity->getCategory());
        $this->assertInternalType('array', $visibilityList);
        $this->assertNotEmpty($visibilityList);
    }
}
