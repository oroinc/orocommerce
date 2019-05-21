<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\PriceListSchedule\Processor;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList\DeleteListProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor\SavePriceListSchedulesToContext;
use Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor\UpdatePriceListsContainSchedule;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;

class UpdatePriceListsContainScheduleTest extends DeleteListProcessorTestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var UpdatePriceListsContainSchedule */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new UpdatePriceListsContainSchedule(
            $this->doctrineHelper
        );
    }

    public function testProcessWhenNoPriceListSchedulesInContext()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->processor->process($this->context);
    }

    public function testProcessWhenEmptyPriceListSchedulesInContext()
    {
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->context->set(SavePriceListSchedulesToContext::PRICE_LIST_SCHEDULES, []);
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $priceList = new PriceList();
        $priceList->setContainSchedule(true);

        $priceListWithSchedule = new PriceList();
        $priceListWithSchedule->addSchedule(new PriceListSchedule());
        $priceListWithSchedule->setContainSchedule(false);

        $schedules = [
            (new PriceListSchedule())->setPriceList($priceList),
            (new PriceListSchedule())->setPriceList($priceListWithSchedule),
            new PriceListSchedule()
        ];

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($this->createMock(EntityManager::class));

        $this->context->set(SavePriceListSchedulesToContext::PRICE_LIST_SCHEDULES, $schedules);
        $this->processor->process($this->context);

        self::assertTrue($priceListWithSchedule->isContainSchedule());
        self::assertFalse($priceList->isContainSchedule());
    }
}
