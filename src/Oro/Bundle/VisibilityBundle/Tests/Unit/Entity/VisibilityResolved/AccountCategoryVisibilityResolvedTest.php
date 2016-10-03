<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\CatalogBundle\Entity\Category;

class AccountCategoryVisibilityResolvedTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var AccountCategoryVisibilityResolved */
    protected $accountCategoryVisibilityResolved;

    /** @var Category */
    protected $category;

    protected function setUp()
    {
        $this->category = new Category();
        $this->accountCategoryVisibilityResolved = new AccountCategoryVisibilityResolved(
            $this->category
        );
    }

    protected function tearDown()
    {
        unset($this->accountCategoryVisibilityResolved, $this->category);
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

    public function testGetCategory()
    {
        $this->assertEquals($this->category, $this->accountCategoryVisibilityResolved->getCategory());
    }
}
