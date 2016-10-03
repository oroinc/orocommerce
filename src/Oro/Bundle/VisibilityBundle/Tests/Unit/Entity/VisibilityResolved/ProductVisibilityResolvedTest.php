<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductVisibilityResolvedTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var ProductVisibilityResolved */
    protected $entity;

    /** @var Product */
    protected $product;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->product = new Product();
        $this->entity = new ProductVisibilityResolved($this->product);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->entity, $this->product);
    }

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $entity = new ProductVisibilityResolved(new Product());

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

    public function testGetProduct()
    {
        $this->assertEquals($this->product, $this->entity->getProduct());
    }
}
