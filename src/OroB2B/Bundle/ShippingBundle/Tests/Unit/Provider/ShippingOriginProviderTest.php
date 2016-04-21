<?php

namespace OroB2B\Bundle\ShippingBundle\Bundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse;
use OroB2B\Bundle\ShippingBundle\Factory\ShippingOriginModelFactory;
use OroB2B\Bundle\ShippingBundle\Model\ShippingOrigin;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingOriginProvider;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;

class ShippingOriginProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var ShippingOriginProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $provider;

    /** @var Warehouse|\PHPUnit_Framework_MockObject_MockObject */
    protected $warehouse;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repo;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var ShippingOriginModelFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingOriginModelFactory;

    protected function setUp()
    {
        $this->repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->warehouse = $this->getMockBuilder('OroB2B\Bundle\WarehouseBundle\Entity\Warehouse')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repo->expects($this->at(0))
            ->method('findOneBy')
            ->willReturnCallback(
                function ($criteria) {
                    if ($criteria['warehouse'] === $this->warehouse) {
                        return (new ShippingOriginWarehouse([]))->setWarehouse($this->warehouse);
                    }

                    return null;
                }
            );

        $this->em->expects($this->at(0))
            ->method('getRepository')
            ->willReturn(
                $this->repo
            );

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with('OroB2B\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse')
            ->willReturn(
                $this->em
            );

        $this->configManager = $this
            ->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingOriginModelFactory = $this
            ->getMockBuilder('OroB2B\Bundle\ShippingBundle\Factory\ShippingOriginModelFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new ShippingOriginProvider(
            $this->doctrineHelper,
            $this->configManager,
            $this->shippingOriginModelFactory
        );
    }

    public function tearDown()
    {
        unset($this->provider, $this->configManager, $this->doctrineHelper, $this->em, $this->repo, $this->warehouse);
    }

    public function testWarehouseWithShippingOrigin()
    {
        $shippingOrigin = $this->provider->getShippingOriginByWarehouse($this->warehouse);

        $this->assertInstanceOf(
            'OroB2B\Bundle\ShippingBundle\Model\ShippingOrigin',
            $shippingOrigin
        );
        $this->assertFalse($shippingOrigin->isSystem());
    }

    public function testWarehouseWithoutShippingOrigin()
    {
        $this->shippingOriginModelFactory->expects($this->once())
            ->method('create')
            ->willReturn(
                new ShippingOrigin()
            );

        $anotherWarehouse = new Warehouse();
        $shippingOrigin = $this->provider->getShippingOriginByWarehouse($anotherWarehouse);

        $this->assertInstanceOf(
            'OroB2B\Bundle\ShippingBundle\Model\ShippingOrigin',
            $shippingOrigin
        );
        $this->assertTrue($shippingOrigin->isSystem());
    }
}
