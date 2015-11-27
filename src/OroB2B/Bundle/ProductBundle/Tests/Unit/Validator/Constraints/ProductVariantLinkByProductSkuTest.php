<?php

namespace OroB2B\Bundle\ProductBundle\Test\Validator\Constraints;

use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductVariantLinkByProductSkuValidator;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductVariantLinksByProductSku;

class ProductVariantLinkByProductSkuTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductVariantLinksByProductSku
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->constraint = new ProductVariantLinksByProductSku();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->constraint);
    }

    public function testValidatedBy()
    {
        $this->assertEquals(ProductVariantLinkByProductSkuValidator::ALIAS, $this->constraint->validatedBy());
    }
}
