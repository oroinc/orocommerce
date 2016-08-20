<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;

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

    public function tearDown()
    {
        unset($this->registry);
    }

    public function testAddProvider()
    {
        $providerName = 'TestProvider';
        $provider = $this->getProviderMock($providerName);
        $this->registry->addProvider($provider);
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
        $this->registry->addProvider($this->getProviderMock($providerName));
        $this->registry->addProvider($this->getProviderMock($providerName));
    }

    public function testGetProvider()
    {
        $providerName = 'TestProvider';
        $expectedProvider = $this->getProviderMock($providerName);
        $this->registry->addProvider($expectedProvider);
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
     * @param string $name
     * @return TaxProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getProviderMock($name)
    {
        $mock = $this->getMock('Oro\Bundle\TaxBundle\Provider\TaxProviderInterface');
        $mock->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        return $mock;
    }
}
