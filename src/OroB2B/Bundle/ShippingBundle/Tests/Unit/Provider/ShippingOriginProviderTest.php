<?php

namespace OroB2B\Bundle\ShippingBundle\Bundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingOriginProvider;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;

class ShippingOriginProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ShippingOriginProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Warehouse */
    protected $warehouse;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository */
    protected $repo;

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

        $this->provider = new ShippingOriginProvider($this->doctrineHelper);
    }

    public function tearDown()
    {
        unset($this->provider);
    }

    public function testWarehouseWithShippingOrigin()
    {
        $shippingOriginWarehouse = $this->provider->getShippingOriginByWarehouse($this->warehouse);
        self::assertInstanceOf(
            'OroB2B\Bundle\ShippingBundle\Entity\ShippingOriginWarehouse',
            $shippingOriginWarehouse
        );
    }

    public function testWarehouseWithoutShippingOrigin()
    {
        $anotherWarehouse = new Warehouse();
        self::assertEmpty($this->provider->getShippingOriginByWarehouse($anotherWarehouse));
        unset($anotherWarehouse);
    }
}
