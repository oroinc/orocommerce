<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\VisibilityResolved;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupProductVisibilityResolved;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CustomerGroupProductVisibilityResolvedTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var CustomerGroupProductVisibilityResolved */
    private $entity;

    /** @var Product */
    private $product;

    /** @var Scope */
    private $scope;

    protected function setUp(): void
    {
        $this->product = new Product();
        $this->scope = new Scope();

        $this->entity = new CustomerGroupProductVisibilityResolved($this->scope, $this->product);
    }

    public function testGettersAndSetters()
    {
        $this->assertPropertyAccessors(
            $this->entity,
            [
                ['visibility', 0],
                ['sourceProductVisibility', new CustomerGroupProductVisibility()],
                ['source', BaseProductVisibilityResolved::VISIBILITY_VISIBLE],
                ['category', new Category()]
            ]
        );
    }

    public function testGetScope()
    {
        $this->assertEquals($this->scope, $this->entity->getScope());
    }

    public function testGetProduct()
    {
        $this->assertEquals($this->product, $this->entity->getProduct());
    }
}
