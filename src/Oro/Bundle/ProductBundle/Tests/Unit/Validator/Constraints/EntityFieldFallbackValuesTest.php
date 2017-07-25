<?php

namespace Oro\Bundle\ProductBundle\Test\Validator\Constraints;

use Oro\Bundle\ProductBundle\Validator\Constraints\EntityFieldFallbackValues;

class EntityFieldFallbackValuesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityFieldFallbackValues
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->constraint = new EntityFieldFallbackValues();
    }

    public function testValidatedBy()
    {
        $this->assertEquals('oro_entity_field_fallback_values_validator', $this->constraint->validatedBy());
    }
}
