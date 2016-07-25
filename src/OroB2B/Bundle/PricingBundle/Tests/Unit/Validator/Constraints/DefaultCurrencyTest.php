<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use OroB2B\Bundle\PricingBundle\Validator\Constraints\DefaultCurrency;

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
        $this->assertEquals('orob2b.pricing.validators.default_currency.message', $this->constraint->message);
    }
}
