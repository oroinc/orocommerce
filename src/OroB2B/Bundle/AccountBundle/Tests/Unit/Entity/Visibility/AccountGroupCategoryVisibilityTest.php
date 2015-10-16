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

        $this->assertPropertyAccessors(
            new AccountGroupCategoryVisibility(),
            [
                ['id', 1],
                ['category', new Category()],
                ['accountGroup', new AccountGroup()],
                ['visibility', AccountGroupCategoryVisibility::CATEGORY],
            ]
        );
        $entity->setCategory(new Category());
        $this->assertEquals(
            AccountGroupCategoryVisibility::CATEGORY,
            AccountGroupCategoryVisibility::getDefault($entity->getCategory())
        );
        $visibilityList = AccountGroupCategoryVisibility::getVisibilityList($entity->getCategory());
        $this->assertInternalType('array', $visibilityList);
        $this->assertNotEmpty($visibilityList);
    }


    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetVisibilityListWithNoArguments()
    {
        AccountGroupCategoryVisibility::getVisibilityList();
    }
}
