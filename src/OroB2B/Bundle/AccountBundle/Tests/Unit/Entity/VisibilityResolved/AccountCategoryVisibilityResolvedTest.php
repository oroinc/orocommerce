<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

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
