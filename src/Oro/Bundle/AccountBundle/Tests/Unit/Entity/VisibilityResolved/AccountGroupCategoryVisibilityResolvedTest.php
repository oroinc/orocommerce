<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupCategoryVisibilityResolved;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\CatalogBundle\Entity\Category;

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
