<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\VisibilityResolved;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupCategoryVisibilityResolved;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CustomerGroupCategoryVisibilityResolvedTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var CustomerGroupCategoryVisibilityResolved */
    private $customerGroupCategoryVisibilityResolved;

    /** @var Category */
    private $category;

    /** @var Scope */
    private $scope;

    protected function setUp(): void
    {
        $this->category = new Category();
        $this->scope = new Scope();

        $this->customerGroupCategoryVisibilityResolved = new CustomerGroupCategoryVisibilityResolved(
            $this->category,
            $this->scope
        );
    }

    public function testGettersAndSetters()
    {
        $this->assertPropertyAccessors(
            $this->customerGroupCategoryVisibilityResolved,
            [
                ['visibility', 0],
                ['sourceCategoryVisibility', new CustomerGroupCategoryVisibility()],
                ['source', BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE],
            ]
        );
    }

    public function testGetScope()
    {
        $this->assertEquals($this->scope, $this->customerGroupCategoryVisibilityResolved->getScope());
    }

    public function testGetCategory()
    {
        $this->assertEquals($this->category, $this->customerGroupCategoryVisibilityResolved->getCategory());
    }
}
