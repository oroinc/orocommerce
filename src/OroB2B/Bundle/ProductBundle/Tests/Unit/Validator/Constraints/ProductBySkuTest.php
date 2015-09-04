<?php

namespace OroB2B\Bundle\ProductBundle\Test\Validator\Constraints;

use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductBySku;

class ProductBySkuTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductBySku
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->constraint = new ProductBySku();
    }

    public function testValidatedBy()
    {
        $this->assertEquals('orob2b_product_product_by_sku_validator', $this->constraint->validatedBy());
    }
}
