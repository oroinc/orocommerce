<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\Visibility;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CustomerCategoryVisibilityTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $entity = new CustomerCategoryVisibility();

        $category = new Category();
        $this->assertPropertyAccessors(
            $entity,
            [
                ['id', 1],
                ['category', $category],
                ['visibility', CustomerCategoryVisibility::CATEGORY],
                ['scope', new Scope()]
            ]
        );

        $entity->setTargetEntity($category);
        $this->assertEquals($entity->getTargetEntity(), $category);

        $this->assertEquals(CustomerCategoryVisibility::CUSTOMER_GROUP, $entity->getDefault($category));

        $visibilityList = CustomerCategoryVisibility::getVisibilityList($category);
        $this->assertIsArray($visibilityList);
        $this->assertNotEmpty($visibilityList);
    }
}
