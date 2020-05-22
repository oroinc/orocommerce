<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Validator\Constraints\SkuRegex;
use Symfony\Component\Validator\Constraint;

class SkuRegexTest extends \PHPUnit\Framework\TestCase
{
    /** @var SkuRegex */
    private $constraint;

    protected function setUp(): void
    {
        $this->constraint = new SkuRegex();
    }

    public function testConfiguration()
    {
        $this->assertEquals('oro_product.validator_constraints.sku_regex_validator', $this->constraint->validatedBy());
        $this->assertEquals(Constraint::PROPERTY_CONSTRAINT, $this->constraint->getTargets());
    }
}
