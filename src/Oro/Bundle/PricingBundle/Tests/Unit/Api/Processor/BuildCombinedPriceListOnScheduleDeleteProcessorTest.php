<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\PricingBundle\Api\Processor\BuildCombinedPriceListOnScheduleDeleteProcessor;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use PHPUnit\Framework\TestCase;

class BuildCombinedPriceListOnScheduleDeleteProcessorTest extends TestCase
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
     * @var BuildCombinedPriceListOnScheduleDeleteProcessor
     */
    private $processor;

    protected function setUp()
    {
        $this->combinedPriceListBuilder = $this->createMock(CombinedPriceListActivationPlanBuilder::class);
        $this->deleteHandler = $this->createMock(ProcessorInterface::class);

        $this->processor = new BuildCombinedPriceListOnScheduleDeleteProcessor(
            $this->combinedPriceListBuilder,
            $this->deleteHandler
        );
    }

    public function testProcessNoData()
    {
        $this->combinedPriceListBuilder->expects(static::any())
            ->method('buildByPriceList');

        $this->deleteHandler->expects(static::any())
            ->method('process');

        $this->processor->process($this->createMock(ContextInterface::class));
    }

    public function testProcess()
    {
        $priceList = new PriceList();

        $context = $this->createMock(ContextInterface::class);
        $context->expects(static::once())
            ->method('getResult')
            ->willReturn((new PriceListSchedule())->setPriceList($priceList));

        $this->deleteHandler->expects(static::once())
            ->method('process');

        $this->combinedPriceListBuilder->expects(static::once())
            ->method('buildByPriceList')
            ->with($priceList);

        $this->processor->process($context);
    }
}
