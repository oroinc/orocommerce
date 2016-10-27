<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;

class ShippingLineItemTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var ShippingLineItem */
    protected $model;

    protected function setUp()
    {
        $this->model = new ShippingLineItem();
    }

    protected function tearDown()
    {
        unset($this->model);
    }

    public function testAccessors()
    {
        static::assertPropertyAccessors(
            $this->model,
            [
                ['product', new Product()],
                ['quantity', 1],
                ['productUnit', new ProductUnit()],
                ['price', new Price()],
                ['weight', new Weight()],
                ['dimensions', new Dimensions()],
                ['entityIdentifier', 1, false],
            ]
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage product is not defined.
     */
    public function testGetProductSKUException()
    {
        $this->model->getProductSku();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage productUnit is not defined.
     */
    public function testGetProductUnitCodeException()
    {
        $this->model->getProductUnitCode();
    }
}
