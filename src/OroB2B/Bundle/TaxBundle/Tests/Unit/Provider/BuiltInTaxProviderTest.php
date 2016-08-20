<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\TaxBundle\Provider\BuiltInTaxProvider;

class BuiltInTaxProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BuiltInTaxProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->provider = new BuiltInTaxProvider();
    }

    public function tearDown()
    {
        unset($this->provider);
    }

    public function testGetName()
    {
        $this->assertEquals('built_in', $this->provider->getName());
    }

    public function testGetLabel()
    {
        $this->assertEquals('oro.tax.providers.built_in.label', $this->provider->getLabel());
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->provider->isApplicable());
    }
}
