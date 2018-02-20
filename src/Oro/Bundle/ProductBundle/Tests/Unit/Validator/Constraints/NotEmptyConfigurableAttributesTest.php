<?php

namespace Oro\Bundle\ProductBundle\Test\Validator\Constraints;

use Oro\Bundle\ProductBundle\Validator\Constraints\NotEmptyConfigurableAttributes;
use Oro\Bundle\ProductBundle\Validator\Constraints\NotEmptyConfigurableAttributesValidator;
use Symfony\Component\Validator\Constraint;

class NotEmptyConfigurableAttributesTest extends \PHPUnit_Framework_TestCase
{
    /** @var NotEmptyConfigurableAttributes */
    private $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->constraint = new NotEmptyConfigurableAttributes();
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
        $this->assertEquals(NotEmptyConfigurableAttributesValidator::ALIAS, $this->constraint->validatedBy());
    }

    public function testGetTargets()
    {
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }
}
