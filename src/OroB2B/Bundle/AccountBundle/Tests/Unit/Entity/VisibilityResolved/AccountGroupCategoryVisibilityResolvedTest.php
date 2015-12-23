<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class AccountGroupCategoryVisibilityResolvedTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var AccountGroupCategoryVisibilityResolved */
    protected $accountGroupCategoryVisibilityResolved;

    /** @var AccountGroup */
    protected $accountGroup;

    /** @var Category */
    protected $category;

    protected function setUp()
    {
        $this->category = new Category();
        $this->accountGroup = new AccountGroup();
        $this->accountGroupCategoryVisibilityResolved = new AccountGroupCategoryVisibilityResolved(
            $this->category,
            $this->accountGroup
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->accountGroupCategoryVisibilityResolved, $this->accountGroup, $this->category);
    }

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            $this->accountGroupCategoryVisibilityResolved,
            [
                ['visibility', 0],
                ['sourceCategoryVisibility', new AccountGroupCategoryVisibility()],
                ['source', BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE],
            ]
        );
    }

    public function testGetAccountGroup()
    {
        $this->assertEquals($this->accountGroup, $this->accountGroupCategoryVisibilityResolved->getAccountGroup());
    }

    public function testGetCategory()
    {
        $this->assertEquals($this->category, $this->accountGroupCategoryVisibilityResolved->getCategory());
    }
}
