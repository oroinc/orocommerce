<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Validator\Constraints\NotEmptyConfigurableAttributes;
use Oro\Bundle\ProductBundle\Validator\Constraints\NotEmptyConfigurableAttributesValidator;
use Symfony\Component\Validator\Constraint;

class NotEmptyConfigurableAttributesTest extends \PHPUnit\Framework\TestCase
{
    /** @var NotEmptyConfigurableAttributes */
    private $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->constraint = new NotEmptyConfigurableAttributes();
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
        $this->assertEquals(NotEmptyConfigurableAttributesValidator::ALIAS, $this->constraint->validatedBy());
    }

    public function testGetTargets()
    {
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }
}
