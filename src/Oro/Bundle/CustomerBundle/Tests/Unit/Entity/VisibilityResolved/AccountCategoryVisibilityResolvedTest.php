<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\CatalogBundle\Entity\Category;

class AccountCategoryVisibilityResolvedTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var AccountCategoryVisibilityResolved */
    protected $accountCategoryVisibilityResolved;

    /** @var Account */
    protected $account;

    /** @var Category */
    protected $category;

    protected function setUp()
    {
        $this->category = new Category();
        $this->account = new Account();
        $this->accountCategoryVisibilityResolved = new AccountCategoryVisibilityResolved(
            $this->category,
            $this->account
        );
    }

    protected function tearDown()
    {
        unset($this->accountCategoryVisibilityResolved, $this->category, $this->account);
    }

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            $this->accountCategoryVisibilityResolved,
            [
                ['visibility', 0],
                ['sourceCategoryVisibility', new AccountCategoryVisibility()],
                ['source', BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE],
            ]
        );
    }

    public function testGetAccount()
    {
        $this->assertEquals($this->account, $this->accountCategoryVisibilityResolved->getAccount());
    }

    public function testGetCategory()
    {
        $this->assertEquals($this->category, $this->accountCategoryVisibilityResolved->getCategory());
    }
}
