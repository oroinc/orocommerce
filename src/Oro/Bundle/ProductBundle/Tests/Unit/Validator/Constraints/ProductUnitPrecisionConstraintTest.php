<?php

namespace Oro\Bundle\ProductBundle\Test\Validator\Constraints;

use Oro\Bundle\ProductBundle\Validator\Constraints\ProductUnitPrecisionConstraint;
use Oro\Bundle\ProductBundle\Validator\ProductUnitPrecisionValidator;

use Symfony\Component\Validator\Constraint;

class ProductUnitPrecisionConstraintTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductUnitPrecisionConstraint */
    private $constraint;

    protected function setUp()
    {
        $this->constraint = new ProductUnitPrecisionConstraint();
    }

    public function testValidatedBy()
    {
        $this->assertSame(ProductUnitPrecisionValidator::ALIAS, $this->constraint->validatedBy());
    }

    public function testGetTargets()
    {
        $this->assertSame(Constraint::PROPERTY_CONSTRAINT, $this->constraint->getTargets());
    }
}
