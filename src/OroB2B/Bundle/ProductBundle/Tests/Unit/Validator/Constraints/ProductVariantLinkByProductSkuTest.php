<?php

namespace OroB2B\Bundle\ProductBundle\Test\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductVariantLinkByProductSkuValidator;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductVariantLinkByProductSku;

class ProductVariantLinkByProductSkuTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductVariantLinkByProductSku
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->constraint = new ProductVariantLinkByProductSku();
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

    public function testGetTargets()
    {
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }
}
