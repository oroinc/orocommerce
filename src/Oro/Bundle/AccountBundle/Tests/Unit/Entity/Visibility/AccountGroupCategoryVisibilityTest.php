<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\CatalogBundle\Entity\Category;

class AccountGroupCategoryVisibilityTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $entity = new AccountGroupCategoryVisibility();
        $category = new Category();
        $this->assertPropertyAccessors(
            new AccountGroupCategoryVisibility(),
            [
                ['id', 1],
                ['category', $category],
                ['accountGroup', new AccountGroup()],
                ['visibility', AccountGroupCategoryVisibility::CATEGORY],
            ]
        );
        $entity->setTargetEntity($category);
        $this->assertEquals($entity->getTargetEntity(), $category);
        $this->assertEquals(AccountGroupCategoryVisibility::CATEGORY, $entity->getDefault($category));

        $visibilityList = AccountGroupCategoryVisibility::getVisibilityList($category);
        $this->assertInternalType('array', $visibilityList);
        $this->assertNotEmpty($visibilityList);
    }
}
