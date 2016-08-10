<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Duplicator;

use Doctrine\Common\Persistence\ObjectManager;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToProduct;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Duplicator\PriceListToProductDuplicator;

class PriceListToProductDuplicatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $manager;

    /**
     * @var InsertFromSelectQueryExecutor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $insertQueryExecutor;

    /**
     * @var PriceListToProductDuplicator
     */
    protected $priceListToProductDuplicator;

    protected function setUp()
    {
        $this->manager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry = $this->getMock(RegistryInterface::class);
        $this->insertQueryExecutor = $this->getMockBuilder(InsertFromSelectQueryExecutor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceListToProductDuplicator = new PriceListToProductDuplicator(
            $this->registry,
            $this->insertQueryExecutor
        );
    }

    public function testDuplicate()
    {
        /** @var PriceList|\PHPUnit_Framework_MockObject_MockObject $sourcePriceList **/
        $sourcePriceList = $this->getMock(PriceList::class);

        /** @var PriceList|\PHPUnit_Framework_MockObject_MockObject $targetPriceList **/
        $targetPriceList = $this->getMock(PriceList::class);

        $entityName = PriceListToProduct::class;
        $this->priceListToProductDuplicator->setEntityName($entityName);

        /** @var \PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->getMockBuilder(PriceListToProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager->expects($this->once())
            ->method('getRepository')
            ->with($entityName)
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($entityName)
            ->willReturn($this->manager);

        $repository->expects($this->once())
            ->method('copyRelations')
            ->with($sourcePriceList, $targetPriceList, $this->insertQueryExecutor);

        $this->priceListToProductDuplicator->duplicate($sourcePriceList, $targetPriceList);
    }

}
