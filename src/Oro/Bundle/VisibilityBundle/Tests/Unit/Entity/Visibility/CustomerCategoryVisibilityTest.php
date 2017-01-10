<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class CustomerCategoryVisibilityTest extends \PHPUnit_Framework_TestCase
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

        $this->assertEquals(CustomerCategoryVisibility::ACCOUNT_GROUP, $entity->getDefault($category));

        $visibilityList = CustomerCategoryVisibility::getVisibilityList($category);
        $this->assertInternalType('array', $visibilityList);
        $this->assertNotEmpty($visibilityList);
    }
}
