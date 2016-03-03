<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\SubtotalProviderRegistry;

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

    /**
     * @param string $name
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|SubtotalProviderInterface
     */
    protected function getProviderMock($name)
    {
        $provider = $this->getMock('OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface');
        $provider->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        return $provider;
    }
}
