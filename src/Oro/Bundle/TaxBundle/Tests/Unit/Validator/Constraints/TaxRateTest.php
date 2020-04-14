<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\TaxBundle\Validator\Constraints\TaxRate;
use Oro\Bundle\TaxBundle\Validator\Constraints\TaxRateValidator;
use Symfony\Component\Validator\Constraint;

class TaxRateTest extends \PHPUnit\Framework\TestCase
{
    /** @var TaxRate */
    private $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->constraint = new TaxRate();
    }

    public function testGetTargets()
    {
        $this->assertEquals(Constraint::PROPERTY_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testValidatedBy()
    {
        $this->assertEquals(TaxRateValidator::ALIAS, $this->constraint->validatedBy());
    }
}
