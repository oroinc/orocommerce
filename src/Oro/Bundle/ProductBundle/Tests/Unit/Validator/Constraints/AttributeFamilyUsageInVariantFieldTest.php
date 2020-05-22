<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Validator\Constraints\AttributeFamilyUsageInVariantField;
use Oro\Bundle\ProductBundle\Validator\Constraints\AttributeFamilyUsageInVariantFieldValidator;
use Symfony\Component\Validator\Constraint;

class AttributeFamilyUsageInVariantFieldTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AttributeFamilyUsageInVariantField
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->constraint = new AttributeFamilyUsageInVariantField();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
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
