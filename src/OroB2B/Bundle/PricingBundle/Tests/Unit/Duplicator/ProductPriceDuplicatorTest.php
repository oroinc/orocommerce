<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Duplicator;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
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

    /**
     * @dataProvider duplicateDataProvider
     * @param PriceList $sourcePriceList
     * @param PriceList $targetPriceList
     */
    public function testDuplicate(PriceList $sourcePriceList, PriceList $targetPriceList)
    {
        $priceListClass = 'OroB2B\Bundle\PricingBundle\Entity\ProductPrice';
        $this->priceDuplicator->setPriceListClass($priceListClass);

        if ($targetPriceList->getPrices()->isEmpty()) {
            $this->manager->expects($this->once())
                ->method('getRepository')
                ->with($priceListClass)
                ->willReturn($this->repository);
            $this->registry->expects($this->once())->method('getManagerForClass')->willReturn($this->manager);
            $this->repository->expects($this->once())
                ->method('copyPrices')
                ->with($sourcePriceList, $targetPriceList, $this->insertExecutor);
        } else {
            $this->manager->expects($this->never())
                ->method('getRepository')
                ->with($priceListClass);
        }

        $this->priceDuplicator->duplicate($sourcePriceList, $targetPriceList);
    }

    public function duplicateDataProvider()
    {
        return [
            'target price list without prices' => [
                'sourcePriceList' => new PriceList(),
                'targetPriceList' => new PriceList()
            ],
            'target price list with prices' => [
                'sourcePriceList' => new PriceList(),
                'targetPriceList' => (new PriceList())->addPrice(new ProductPrice())
            ],
        ];
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
