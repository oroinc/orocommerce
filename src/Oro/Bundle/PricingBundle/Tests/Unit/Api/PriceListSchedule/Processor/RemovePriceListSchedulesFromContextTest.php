<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\PriceListSchedule\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList\DeleteListProcessorTestCase;
use Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor\RemovePriceListSchedulesFromContext;
use Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor\SavePriceListSchedulesToContext;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;

class RemovePriceListSchedulesFromContextTest extends DeleteListProcessorTestCase
{
    /** @var RemovePriceListSchedulesFromContext */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new RemovePriceListSchedulesFromContext();
    }

    public function testProcessWhenNoPriceListSchedulesInContext()
    {
        $this->processor->process($this->context);

        self::assertFalse($this->context->has(SavePriceListSchedulesToContext::PRICE_LIST_SCHEDULES));
    }

    public function testProcess()
    {
        $this->context->set(
            SavePriceListSchedulesToContext::PRICE_LIST_SCHEDULES,
            [new PriceListSchedule()]
        );
        $this->processor->process($this->context);

        self::assertFalse($this->context->has(SavePriceListSchedulesToContext::PRICE_LIST_SCHEDULES));
    }
}
