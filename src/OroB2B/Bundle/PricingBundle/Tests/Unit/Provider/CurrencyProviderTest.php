<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\PricingBundle\Provider\CurrencyProvider;

class CurrencyProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CurrencyProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->provider = new CurrencyProvider($this->registry, '\stdClass');
    }

    protected function tearDown()
    {
        unset($this->provider, $this->registry);
    }

    public function testGetAvailableCurrencies()
    {
        $data = ['USD' => 'USD', 'EUR' => 'EUR'];

        $repository = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->once())
            ->method('getAvailableCurrencies')
            ->willReturn($data);

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('\stdClass'))
            ->willReturn($repository);

        $this->assertEquals($data, $this->provider->getAvailableCurrencies());
    }
}
