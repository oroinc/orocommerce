<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Duplicator;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\PricingBundle\Duplicator\ProductPriceDuplicator;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class ProductPriceDuplicatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ProductPriceDuplicator
     */
    protected $priceDuplicator;

    /**
     * @var InsertFromSelectQueryExecutor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $insertExecutor;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $manager;

    /**
     * @var ProductPriceRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    public function setUp()
    {
        $this->registry = $this->getMockWithoutConstructor('Symfony\Bridge\Doctrine\ManagerRegistry');
        $this->insertExecutor = $this
            ->getMockWithoutConstructor('Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor');
        $this->manager = $this->getMockWithoutConstructor('Doctrine\Common\Persistence\ObjectManager');

        $this->priceDuplicator = new ProductPriceDuplicator(
            $this->registry,
            $this->insertExecutor
        );
        $this->repository = $this
            ->getMockWithoutConstructor('OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository');
    }

    public function testDuplicate()
    {
        $priceListClass = 'OroB2B\Bundle\PricingBundle\Entity\ProductPrice';
        $this->priceDuplicator->setPriceListClass($priceListClass);

        $this->manager->expects($this->once())
            ->method('getRepository')
            ->with($priceListClass)
            ->willReturn($this->repository);
        $this->registry->expects($this->once())->method('getManagerForClass')->willReturn($this->manager);

        $priceListTarget = new PriceList();
        $priceListSource = new PriceList();
        $this->repository->expects($this->once())
            ->method('copyPrices')
            ->with($priceListSource, $priceListTarget, $this->insertExecutor);
        $this->priceDuplicator->duplicate($priceListSource, $priceListTarget);
    }

    /**
     * @param $className
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockWithoutConstructor($className)
    {
        return $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
    }
}
