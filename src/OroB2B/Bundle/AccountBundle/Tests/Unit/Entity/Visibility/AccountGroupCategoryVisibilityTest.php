<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

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
