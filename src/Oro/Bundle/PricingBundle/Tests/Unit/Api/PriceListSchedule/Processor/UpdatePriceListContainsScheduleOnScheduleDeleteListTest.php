<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\PriceListSchedule\Processor;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList\DeleteListProcessorTestCase;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor\UpdatePriceListContainsScheduleOnScheduleDeleteList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ProcessorInterface;

class UpdatePriceListContainsScheduleOnScheduleDeleteListTest extends DeleteListProcessorTestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var ProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deleteHandler;

    /**
     * @var UpdatePriceListContainsScheduleOnScheduleDeleteList
     */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->deleteHandler = $this->createMock(ProcessorInterface::class);

        $this->processor = new UpdatePriceListContainsScheduleOnScheduleDeleteList(
            $this->doctrineHelper,
            $this->deleteHandler
        );
    }

    public function testProcessNotArray()
    {
        $this->doctrineHelper->expects(static::never())
            ->method('getEntityManager');

        $this->deleteHandler->expects(static::once())
            ->method('process');

        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $priceList = new PriceList();
        $priceList->setContainSchedule(true);

        $priceListWithSchedule = new PriceList();
        $priceListWithSchedule->addSchedule(new PriceListSchedule());
        $priceListWithSchedule->setContainSchedule(false);

        $this->deleteHandler->expects(static::once())
            ->method('process');

        $this->doctrineHelper->expects(static::once())
            ->method('getEntityManager')
            ->willReturn($this->createMock(EntityManager::class));

        $this->context->setResult([
            (new PriceListSchedule())->setPriceList($priceList),
            (new PriceListSchedule())->setPriceList($priceListWithSchedule),
            new \StdClass(),
            new PriceListSchedule(),
        ]);
        $this->processor->process($this->context);

        static::assertTrue($priceListWithSchedule->isContainSchedule());
        static::assertFalse($priceList->isContainSchedule());
    }
}
