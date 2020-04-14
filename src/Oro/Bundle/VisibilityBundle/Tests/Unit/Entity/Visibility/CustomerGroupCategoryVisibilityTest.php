<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\Visibility;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CustomerGroupCategoryVisibilityTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $entity = new CustomerGroupCategoryVisibility();
        $category = new Category();
        $this->assertPropertyAccessors(
            new CustomerGroupCategoryVisibility(),
            [
                ['id', 1],
                ['category', $category],
                ['visibility', CustomerGroupCategoryVisibility::CATEGORY],
                ['scope', new Scope()]
            ]
        );
        $entity->setTargetEntity($category);
        $this->assertEquals($entity->getTargetEntity(), $category);
        $this->assertEquals(CustomerGroupCategoryVisibility::CATEGORY, $entity->getDefault($category));

        $visibilityList = CustomerGroupCategoryVisibility::getVisibilityList($category);
        $this->assertIsArray($visibilityList);
        $this->assertNotEmpty($visibilityList);
        $this->assertEquals(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            CustomerGroupCategoryVisibility::getScopeType()
        );
    }
}
