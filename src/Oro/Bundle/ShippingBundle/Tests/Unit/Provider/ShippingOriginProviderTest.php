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
            $this->doctrineHelper,
            $this->configManager,
            $this->shippingOriginModelFactory
        );
    }

    /**
     * @dataProvider shippingOriginProvider
     *
     * @param Warehouse $warehouse
     * @param ShippingOriginWarehouse|null $shippingOriginWarehouse
     * @param string $expectedClass
     * @param bool $expectedIsSystem
     */
    public function testGetShippingOriginByWarehouse(
        Warehouse $warehouse,
        $shippingOriginWarehouse,
        $expectedClass,
        $expectedIsSystem
    ) {
        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['warehouse' => $warehouse])
            ->willReturn($shippingOriginWarehouse)
        ;

        $entityManager = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with('Oro\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse')
            ->willReturn($repository)
        ;

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with('Oro\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse')
            ->willReturn($entityManager)
        ;

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_shipping.shipping_origin')
            ->willReturn([])
        ;

        $shippingOrigin = $this->shippingOriginProvider->getShippingOriginByWarehouse($warehouse);

        $this->assertInstanceOf($expectedClass, $shippingOrigin);
        $this->assertEquals($expectedIsSystem, $shippingOrigin->isSystem());
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
    public function shippingOriginProvider()
    {
        $warehouse = $this->getEntity(Warehouse::class, ['id' => 1, 'name' => 'Warehouse.1']);
        $data = new \ArrayObject();

        return [
            [
                'warehouse' => $warehouse,
                'shippingOriginWarehouse' => $this->getEntity(
                    ShippingOriginWarehouse::class,
                    [
                        'warehouse' => $warehouse,
                        'data' => $data->offsetSet('postalCode', '12345')
                    ],
                    []
                ),
                'expectedClass' => ShippingOrigin::class,
                'expectedIsSystem' => false
            ],
            [
                'warehouse' => $warehouse,
                'shippingOriginWarehouse' => null,
                'expectedClass' => ShippingOrigin::class,
                'expectedIsSystem' => true
            ]
        ];
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
