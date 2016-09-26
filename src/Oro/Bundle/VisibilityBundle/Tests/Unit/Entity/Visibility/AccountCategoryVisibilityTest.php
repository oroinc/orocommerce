<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\CatalogBundle\Entity\Category;

class AccountCategoryVisibilityTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $entity = new AccountCategoryVisibility();

        $category = new Category();
        $this->assertPropertyAccessors(
            $entity,
            [
                ['id', 1],
                ['category', $category],
                ['account', new Account()],
                ['visibility', AccountCategoryVisibility::CATEGORY],
            ]
        );

        $entity->setTargetEntity($category);
        $this->assertEquals($entity->getTargetEntity(), $category);

        $this->assertEquals(AccountCategoryVisibility::ACCOUNT_GROUP, $entity->getDefault($category));

        $visibilityList = AccountCategoryVisibility::getVisibilityList($category);
        $this->assertInternalType('array', $visibilityList);
        $this->assertNotEmpty($visibilityList);
    }
}
