<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse;
use Oro\Bundle\ShippingBundle\Factory\ShippingOriginModelFactory;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;
use Oro\Bundle\WarehouseProBundle\Entity\Warehouse;

class ShippingOriginProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var ShippingOriginModelFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingOriginModelFactory;

    /**
     * @var ShippingOriginProvider
     */
    protected $shippingOriginProvider;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->shippingOriginModelFactory = new ShippingOriginModelFactory($this->doctrineHelper);

        $this->shippingOriginProvider = new ShippingOriginProvider(
            $this->configManager,
            $this->shippingOriginModelFactory
        );
    }

    /**
     * @dataProvider systemShippingOriginProvider
     *
     * @param array $configData
     * @param string $expectedCountry
     * @param string $expectedRegion
     */
    public function testGetSystemShippingOrigin($configData, $expectedCountry, $expectedRegion)
    {
        $country = new Country($configData['country']);
        $this->doctrineHelper->expects($this->at(0))
            ->method('getEntityReference')
            ->with('OroAddressBundle:Country', $configData['country'])
            ->willReturn($country)
        ;

        $region = new Region($configData['region']);
        $this->doctrineHelper->expects($this->at(1))
            ->method('getEntityReference')
            ->with('OroAddressBundle:Region', $configData['region'])
            ->willReturn($region)
        ;

        $shippingOrigin = $this->shippingOriginModelFactory->create($configData);

        $this->assertEquals($expectedCountry, $shippingOrigin->getCountry());
        $this->assertEquals($expectedRegion, $shippingOrigin->getRegion());
    }

    /**
     * @return array
     */
    public function systemShippingOriginProvider()
    {
        return [
            [
                'configData' => [
                    'country' => 'US',
                    'region' => 'US-AL',
                ],
                'expectedCountry' => new Country('US'),
                'expectedRegion' => new Region('US-AL')
            ]
        ];
    }
}
