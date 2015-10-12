<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class AccountCategoryVisibilityTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $entity = new AccountCategoryVisibility();

        $this->assertPropertyAccessors(
            $entity,
            [
                ['id', 1],
                ['category', new Category()],
                ['account', new Account()],
                ['visibility', AccountCategoryVisibility::CATEGORY],
            ]
        );
        $entity->setCategory(new Category());
        $this->assertEquals(
            AccountCategoryVisibility::ACCOUNT_GROUP,
            AccountCategoryVisibility::getDefault($entity->getCategory())
        );

        $visibilityList = AccountCategoryVisibility::getVisibilityList($entity->getCategory());
        $this->assertInternalType('array', $visibilityList);
        $this->assertNotEmpty($visibilityList);
    }
}
