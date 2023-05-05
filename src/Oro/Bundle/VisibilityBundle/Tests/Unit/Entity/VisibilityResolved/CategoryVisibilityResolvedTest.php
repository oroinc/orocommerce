<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\VisibilityResolved;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CategoryVisibilityResolvedTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var CategoryVisibilityResolved */
    private $categoryVisibilityResolved;

    /** @var Category */
    private $category;

    protected function setUp(): void
    {
        $this->category = new Category();
        $this->categoryVisibilityResolved = new CategoryVisibilityResolved($this->category);
    }

    public function testGettersAndSetters()
    {
        $categoryVisibilityResolved = new CategoryVisibilityResolved(new Category());

        $this->assertPropertyAccessors(
            $categoryVisibilityResolved,
            [
                ['visibility', 0],
                ['sourceCategoryVisibility', new CategoryVisibility()],
                ['source', BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE],
            ]
        );
    }

    public function testGetCategory()
    {
        $this->assertEquals($this->category, $this->categoryVisibilityResolved->getCategory());
    }
}
