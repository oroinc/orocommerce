<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method;

class CountryAwarePaymentMethodTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testContextEmpty()
    {
        $stub = new CountryAwarePaymentMethodTraitStub([]);

        $this->assertFalse($stub->isCountryApplicable());
    }

    public function testAllCountriesAllowed()
    {
        $stub = new CountryAwarePaymentMethodTraitStub([], true);

        $this->assertTrue($stub->isCountryApplicable());
    }

    public function testNoSelectedCountries()
    {
        $stub = new CountryAwarePaymentMethodTraitStub([]);

        $this->assertFalse($stub->isCountryApplicable(['country' => 'US']));
    }

    public function testNotAllowed()
    {
        $stub = new CountryAwarePaymentMethodTraitStub(['UK']);

        $this->assertFalse($stub->isCountryApplicable(['country' => 'US']));
    }

    public function testAllowed()
    {
        $stub = new CountryAwarePaymentMethodTraitStub(['UK', 'US']);

        $this->assertTrue($stub->isCountryApplicable(['country' => 'US']));
    }

    public function testDoNotRelyOnConfigValueType()
    {
        $stub = new CountryAwarePaymentMethodTraitStub(['UK', 'US', new \stdClass()]);

        $this->assertTrue($stub->isCountryApplicable(['country' => 'US']));
    }

    public function testDoNotRelyOnContextValueType()
    {
        $stub = new CountryAwarePaymentMethodTraitStub(['UK', 'US', new \stdClass()]);

        $this->assertFalse($stub->isCountryApplicable(['country' => new \stdClass()]));
    }
}
