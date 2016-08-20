<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class ProductVisibilityResolvedTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var ProductVisibilityResolved */
    protected $entity;

    /** @var Product */
    protected $product;

    /** @var Website */
    protected $website;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->website = new Website();
        $this->product = new Product();
        $this->entity = new ProductVisibilityResolved($this->website, $this->product);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->entity, $this->website, $this->product);
    }

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $entity = new ProductVisibilityResolved(new Website(), new Product());

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

    public function testGetWebsite()
    {
        $this->assertEquals($this->website, $this->entity->getWebsite());
    }

    public function testGetProduct()
    {
        $this->assertEquals($this->product, $this->entity->getProduct());
    }
}
