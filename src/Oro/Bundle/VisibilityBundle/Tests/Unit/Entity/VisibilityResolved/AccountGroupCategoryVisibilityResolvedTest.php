<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\VisibilityResolved;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountGroupCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class AccountGroupCategoryVisibilityResolvedTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var AccountGroupCategoryVisibilityResolved */
    protected $accountGroupCategoryVisibilityResolved;

    /** @var Category */
    protected $category;

    /** @var Scope */
    protected $scope;

    protected function setUp()
    {
        $this->category = new Category();
        $this->scope = new Scope();
        $this->accountGroupCategoryVisibilityResolved = new AccountGroupCategoryVisibilityResolved(
            $this->category,
            $this->scope
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->accountGroupCategoryVisibilityResolved, $this->category, $this->scope);
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

    public function testGetScope()
    {
        $this->assertEquals($this->scope, $this->accountGroupCategoryVisibilityResolved->getScope());
    }

    public function testGetCategory()
    {
        $this->assertEquals($this->category, $this->accountGroupCategoryVisibilityResolved->getCategory());
    }
}
