<?php

namespace Oro\Bundle\ProductBundle\Test\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\ProductBundle\Validator\Constraints\AttributeFamilyUsageInVariantField;
use Oro\Bundle\ProductBundle\Validator\Constraints\AttributeFamilyUsageInVariantFieldValidator;

class AttributeFamilyUsageInVariantFieldTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AttributeFamilyUsageInVariantField
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->constraint = new AttributeFamilyUsageInVariantField();
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
        $this->assertEquals(AttributeFamilyUsageInVariantFieldValidator::ALIAS, $this->constraint->validatedBy());
    }

    public function testGetTargets()
    {
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }
}
