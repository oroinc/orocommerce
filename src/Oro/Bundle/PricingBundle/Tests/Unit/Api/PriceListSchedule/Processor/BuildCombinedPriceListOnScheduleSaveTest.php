<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\PriceListSchedule\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor\BuildCombinedPriceListOnScheduleSave;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;

class BuildCombinedPriceListOnScheduleSaveTest extends FormProcessorTestCase
{
    /**
     * @var CombinedPriceListActivationPlanBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $combinedPriceListBuilder;

    /**
     * @var BuildCombinedPriceListOnScheduleSave
     */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->combinedPriceListBuilder = $this->createMock(CombinedPriceListActivationPlanBuilder::class);

        $this->processor = new BuildCombinedPriceListOnScheduleSave(
            $this->combinedPriceListBuilder
        );
    }

    public function testProcessWrongType()
    {
        $this->combinedPriceListBuilder->expects(static::never())
            ->method('buildByPriceList');

        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $priceList = new PriceList();

        $this->combinedPriceListBuilder->expects(static::once())
            ->method('buildByPriceList')
            ->with($priceList);

        $this->context->setResult((new PriceListSchedule())->setPriceList($priceList));
        $this->processor->process($this->context);
    }
}
