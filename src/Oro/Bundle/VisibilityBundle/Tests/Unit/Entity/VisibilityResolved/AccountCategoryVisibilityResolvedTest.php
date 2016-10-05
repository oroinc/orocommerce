<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class AccountCategoryVisibilityResolvedTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var AccountCategoryVisibilityResolved */
    protected $accountCategoryVisibilityResolved;

    /** @var Category */
    protected $category;

    /** @var Scope */
    protected $scope;

    protected function setUp()
    {
        $this->category = new Category();
        $this->scope = new Scope();
        $this->accountCategoryVisibilityResolved = new AccountCategoryVisibilityResolved(
            $this->category,
            $this->scope
        );
    }

    protected function tearDown()
    {
        unset($this->accountCategoryVisibilityResolved, $this->category, $this->scope);
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

    public function testGetScope()
    {
        $this->assertEquals($this->scope, $this->accountCategoryVisibilityResolved->getScope());
    }

    public function testGetCategory()
    {
        $this->assertEquals($this->category, $this->accountCategoryVisibilityResolved->getCategory());
    }
}
