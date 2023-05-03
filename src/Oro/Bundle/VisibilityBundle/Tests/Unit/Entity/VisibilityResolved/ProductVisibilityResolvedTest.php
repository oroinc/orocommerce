<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\VisibilityResolved;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ProductVisibilityResolvedTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var ProductVisibilityResolved */
    private $entity;

    /** @var Product */
    private $product;

    /** @var Scope */
    private $scope;

    protected function setUp(): void
    {
        $this->product = new Product();
        $this->scope = new Scope();

        $this->entity = new ProductVisibilityResolved($this->scope, $this->product);
    }

    public function testGettersAndSetters()
    {
        $entity = new ProductVisibilityResolved(new Scope, new Product());

        $this->assertPropertyAccessors(
            $entity,
            [
                ['visibility', 0],
                ['sourceProductVisibility', new ProductVisibility()],
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
