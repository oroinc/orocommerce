<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\PriceListSchedule\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Delete\DeleteProcessorTestCase;
use Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor\BuildCombinedPriceListOnScheduleDelete;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ProcessorInterface;

class BuildCombinedPriceListOnScheduleDeleteTest extends DeleteProcessorTestCase
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
     * @var BuildCombinedPriceListOnScheduleDelete
     */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->combinedPriceListBuilder = $this->createMock(CombinedPriceListActivationPlanBuilder::class);
        $this->deleteHandler = $this->createMock(ProcessorInterface::class);

        $this->processor = new BuildCombinedPriceListOnScheduleDelete(
            $this->combinedPriceListBuilder,
            $this->deleteHandler
        );
    }

    public function testProcessWrongType()
    {
        $this->combinedPriceListBuilder->expects(static::never())
            ->method('buildByPriceList');

        $this->deleteHandler->expects(static::once())
            ->method('process');

        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $priceList = new PriceList();

        $this->deleteHandler->expects(static::once())
            ->method('process');

        $this->combinedPriceListBuilder->expects(static::once())
            ->method('buildByPriceList')
            ->with($priceList);

        $this->context->setResult((new PriceListSchedule())->setPriceList($priceList));
        $this->processor->process($this->context);
    }
}
