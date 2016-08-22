<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\PricingBundle\Validator\Constraints\DefaultCurrency;

class DefaultCurrencyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DefaultCurrency
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->constraint = new DefaultCurrency();
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        unset($this->constraint);
    }

    public function testConfiguration()
    {
        $this->assertEquals('orob2b_pricing_default_currency_validator', $this->constraint->validatedBy());
        $this->assertEquals('oro.pricing.validators.default_currency.message', $this->constraint->message);
    }
}
