<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\SubtotalProviderRegistry;

class SubtotalProviderRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testRegistry()
    {
        $name = 'provider';
        $providerMock = $this->getProviderMock($name);

        $registry = new SubtotalProviderRegistry();

        $this->assertEmpty($registry->getProviders());
        $this->assertNull($registry->getProviderByName($name));

        $registry->addProvider($providerMock);

        $this->assertCount(1, $registry->getProviders());
        $this->assertTrue($registry->hasProvider($name));
        $this->assertEquals($providerMock, $registry->getProviderByName($name));
    }

    public function testRegistryGetSupportedProviders()
    {
        $entity = new \stdClass();
        $name = 'provider';
        $providerMock1 = $this->getProviderMock($name);
        $providerMock2 = $this->getProviderMock('provider2');
        $providerMock1->expects($this->once())
            ->method('isSupported')
            ->willReturn(true);
        $providerMock2->expects($this->once())
            ->method('isSupported')
            ->willReturn(false);

        $registry = new SubtotalProviderRegistry();

        $this->assertEmpty($registry->getSupportedProviders($entity));
        $this->assertNull($registry->getProviderByName($name));

        $registry->addProvider($providerMock1);
        $registry->addProvider($providerMock2);

        $this->assertCount(1, $registry->getSupportedProviders($entity));
        $this->assertTrue($registry->hasProvider($name));
        $this->assertEquals($providerMock1, $registry->getProviderByName($name));
    }

    /**
     * @param string $name
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|SubtotalProviderInterface
     */
    protected function getProviderMock($name)
    {
        $provider = $this->getMock('Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface');
        $provider->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        return $provider;
    }
}
