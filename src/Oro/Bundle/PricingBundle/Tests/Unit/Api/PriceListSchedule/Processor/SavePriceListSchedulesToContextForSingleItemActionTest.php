<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\PriceListSchedule\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Delete\DeleteProcessorTestCase;
use Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor\SavePriceListSchedulesToContext;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;

class SavePriceListSchedulesToContextForSingleItemActionTest extends DeleteProcessorTestCase
{
    /** @var SavePriceListSchedulesToContext */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new SavePriceListSchedulesToContext();
    }

    public function testProcessWhenNoPriceListScheduleInContext()
    {
        $this->processor->process($this->context);

        self::assertFalse($this->context->has(SavePriceListSchedulesToContext::PRICE_LIST_SCHEDULES));
    }

    public function testProcessWhenWrongDataInContext()
    {
        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);

        self::assertFalse($this->context->has(SavePriceListSchedulesToContext::PRICE_LIST_SCHEDULES));
    }

    public function testProcess()
    {
        $priceListSchedule = new PriceListSchedule();

        $this->context->setResult($priceListSchedule);
        $this->processor->process($this->context);

        self::assertTrue($this->context->has(SavePriceListSchedulesToContext::PRICE_LIST_SCHEDULES));
        self::assertSame(
            [$priceListSchedule],
            $this->context->get(SavePriceListSchedulesToContext::PRICE_LIST_SCHEDULES)
        );
    }
}
