<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Provider;

use OroB2B\Bundle\TaxBundle\Provider\TaxProviderInterface;
use OroB2B\Bundle\TaxBundle\Provider\TaxProviderRegistry;

class TaxProviderRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TaxProviderRegistry
     */
    protected $registry;

    public function setUp()
    {
        $this->registry = new TaxProviderRegistry();
    }

    public function testAddProvider()
    {
        $provider = $this->getProviderMock();
        $providerName = 'TestProvider';
        $this->registry->addProvider($providerName, $provider);
        $this->assertCount(1, $this->registry->getProviders());
        $this->assertSame($provider, current($this->registry->getProviders()));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Tax provider with name "TestProvider" already registered
     */
    public function testAddTwoProvidersWithSameName()
    {
        $providerName = 'TestProvider';
        $this->registry->addProvider($providerName, $this->getProviderMock());
        $this->registry->addProvider($providerName, $this->getProviderMock());
    }

    public function testGetProvider()
    {
        $providerName = 'TestProvider';
        $expectedProvider = $this->getProviderMock();
        $this->registry->addProvider($providerName, $expectedProvider);
        $actualProvider = $this->registry->getProvider($providerName);

        $this->assertSame($expectedProvider, $actualProvider);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Tax provider with name "someProviderName" does not exist
     */
    public function testGetProviderWithUnknownName()
    {
        $this->registry->getProvider('someProviderName');
    }

    /**
     * @return TaxProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getProviderMock()
    {
        return $this->getMock('OroB2B\Bundle\TaxBundle\Provider\TaxProviderInterface');
    }
}
