<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\VisibilityResolved;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class AccountProductVisibilityResolvedTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var AccountProductVisibilityResolved */
    protected $entity;

    /** @var Product */
    protected $product;

    /** @var  @var Scope */
    protected $scope;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->product = new Product();
        $this->scope = new Scope();
        $this->entity = new AccountProductVisibilityResolved($this->scope, $this->product);
    }

    protected function tearDown()
    {
        unset($this->entity, $this->product, $this->scope);
    }

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            $this->entity,
            [
                ['visibility', 0],
                ['sourceProductVisibility', new AccountProductVisibility()],
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
