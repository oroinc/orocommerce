<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\PriceListSchedule\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList\DeleteListProcessorTestCase;
use Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor\BuildCombinedPriceListOnScheduleDeleteList;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ProcessorInterface;

class BuildCombinedPriceListOnScheduleDeleteListTest extends DeleteListProcessorTestCase
{
    /**
     * @var CombinedPriceListActivationPlanBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $combinedPriceListBuilder;

    /**
     * @var ProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deleteHandler;

    /**
     * @var BuildCombinedPriceListOnScheduleDeleteList
     */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->combinedPriceListBuilder = $this->createMock(CombinedPriceListActivationPlanBuilder::class);
        $this->deleteHandler = $this->createMock(ProcessorInterface::class);

        $this->processor = new BuildCombinedPriceListOnScheduleDeleteList(
            $this->combinedPriceListBuilder,
            $this->deleteHandler
        );
    }

    public function testProcessNotArray()
    {
        $this->combinedPriceListBuilder->expects(static::never())
            ->method('buildByPriceList');

        $this->deleteHandler->expects(static::once())
            ->method('process');

        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $priceLists = [
            $this->createPriceList(1),
            $this->createPriceList(1),
            $this->createPriceList(2),
        ];
        $schedules = [
            (new PriceListSchedule())->setPriceList($priceLists[0]),
            (new PriceListSchedule())->setPriceList($priceLists[1]),
            (new PriceListSchedule())->setPriceList($priceLists[2]),
        ];

        $this->deleteHandler->expects(static::once())
            ->method('process');

        $this->combinedPriceListBuilder->expects(static::exactly(2))
            ->method('buildByPriceList')
            ->withConsecutive(
                [$priceLists[0]],
                [$priceLists[2]]
            );

        $this->context->setResult($schedules);
        $this->processor->process($this->context);
    }

    public function testProcessWrongType()
    {
        $schedules = [
            new \StdClass(),
            new PriceListSchedule(),
        ];

        $this->deleteHandler->expects(static::once())
            ->method('process');

        $this->combinedPriceListBuilder->expects(static::never())
            ->method('buildByPriceList');

        $this->context->setResult($schedules);
        $this->processor->process($this->context);
    }

    /**
     * @param int $id
     *
     * @return PriceList|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createPriceList(int $id)
    {
        $priceList = $this->createMock(PriceList::class);
        $priceList->expects(static::any())
            ->method('getId')
            ->willReturn($id);

        return $priceList;
    }
}
