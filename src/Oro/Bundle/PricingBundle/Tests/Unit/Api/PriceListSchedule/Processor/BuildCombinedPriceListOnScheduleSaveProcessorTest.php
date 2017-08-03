<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\PriceListSchedule\Processor;

use Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor\BuildCombinedPriceListOnScheduleSaveProcessor;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ContextInterface;
use PHPUnit\Framework\TestCase;

class BuildCombinedPriceListOnScheduleSaveProcessorTest extends TestCase
{
    /**
     * @var CombinedPriceListActivationPlanBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $combinedPriceListBuilder;

    /**
     * @var BuildCombinedPriceListOnScheduleSaveProcessor
     */
    private $processor;

    protected function setUp()
    {
        $this->combinedPriceListBuilder = $this->createMock(CombinedPriceListActivationPlanBuilder::class);

        $this->processor = new BuildCombinedPriceListOnScheduleSaveProcessor(
            $this->combinedPriceListBuilder
        );
    }

    public function testProcessWrongType()
    {
        $this->combinedPriceListBuilder->expects(static::never())
            ->method('buildByPriceList');

        $this->processor->process($this->createMock(ContextInterface::class));
    }

    public function testProcess()
    {
        $priceList = new PriceList();

        $context = $this->createMock(ContextInterface::class);
        $context->expects(static::once())
            ->method('getResult')
            ->willReturn((new PriceListSchedule())->setPriceList($priceList));

        $this->combinedPriceListBuilder->expects(static::once())
            ->method('buildByPriceList')
            ->with($priceList);

        $this->processor->process($context);
    }
}
