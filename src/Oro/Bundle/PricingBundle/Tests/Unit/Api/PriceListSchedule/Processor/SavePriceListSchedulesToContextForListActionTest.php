<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\PriceListSchedule\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList\DeleteListProcessorTestCase;
use Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor\SavePriceListSchedulesToContext;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;

class SavePriceListSchedulesToContextForListActionTest extends DeleteListProcessorTestCase
{
    /** @var SavePriceListSchedulesToContext */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new SavePriceListSchedulesToContext();
    }

    public function testProcessWhenNoPriceListSchedulesInContext()
    {
        $this->processor->process($this->context);

        self::assertFalse($this->context->has(SavePriceListSchedulesToContext::PRICE_LIST_SCHEDULES));
    }

    public function testProcessWhenWrongDataInContext()
    {
        $this->context->setResult([new \stdClass()]);
        $this->processor->process($this->context);

        self::assertFalse($this->context->has(SavePriceListSchedulesToContext::PRICE_LIST_SCHEDULES));
    }

    public function testProcess()
    {
        $priceListSchedules = [new PriceListSchedule()];

        $this->context->setResult($priceListSchedules);
        $this->processor->process($this->context);

        self::assertTrue($this->context->has(SavePriceListSchedulesToContext::PRICE_LIST_SCHEDULES));
        self::assertSame(
            $priceListSchedules,
            $this->context->get(SavePriceListSchedulesToContext::PRICE_LIST_SCHEDULES)
        );
    }
}
