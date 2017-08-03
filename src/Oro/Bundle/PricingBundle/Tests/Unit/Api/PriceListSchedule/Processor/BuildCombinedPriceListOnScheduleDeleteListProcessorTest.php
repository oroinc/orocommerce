<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\PriceListSchedule\Processor;

use Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor\BuildCombinedPriceListOnScheduleDeleteListProcessor;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use PHPUnit\Framework\TestCase;

class BuildCombinedPriceListOnScheduleDeleteListProcessorTest extends TestCase
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
     * @var BuildCombinedPriceListOnScheduleDeleteListProcessor
     */
    private $processor;

    protected function setUp()
    {
        $this->combinedPriceListBuilder = $this->createMock(CombinedPriceListActivationPlanBuilder::class);
        $this->deleteHandler = $this->createMock(ProcessorInterface::class);

        $this->processor = new BuildCombinedPriceListOnScheduleDeleteListProcessor(
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

        $this->processor->process($this->createMock(ContextInterface::class));
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

        $context = $this->createMock(ContextInterface::class);
        $context->expects(static::once())
            ->method('getResult')
            ->willReturn($schedules);

        $this->deleteHandler->expects(static::once())
            ->method('process');

        $this->combinedPriceListBuilder->expects(static::exactly(2))
            ->method('buildByPriceList')
            ->withConsecutive(
                [$priceLists[0]],
                [$priceLists[2]]
            );

        $this->processor->process($context);
    }

    public function testProcessWrongType()
    {
        $schedules = [
            new \StdClass(),
            new PriceListSchedule(),
        ];

        $context = $this->createMock(ContextInterface::class);
        $context->expects(static::once())
            ->method('getResult')
            ->willReturn($schedules);

        $this->deleteHandler->expects(static::once())
            ->method('process');

        $this->combinedPriceListBuilder->expects(static::never())
            ->method('buildByPriceList');

        $this->processor->process($context);
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
