<?php

namespace Oro\Bundle\ProductBundle\Test\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\ProductBundle\Validator\Constraints\ProductVariantField;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductVariantFieldValidator;

class ProductVariantFieldTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductVariantField
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->constraint = new ProductVariantField();
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
        $this->assertEquals(ProductVariantFieldValidator::ALIAS, $this->constraint->validatedBy());
    }

    public function testGetTargets()
    {
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }
}
