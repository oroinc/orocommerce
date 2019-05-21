<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\PriceListSchedule\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList\DeleteListProcessorTestCase;
use Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor\BuildCombinedPriceLists;
use Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor\SavePriceListSchedulesToContext;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;

class BuildCombinedPriceListsTest extends DeleteListProcessorTestCase
{
    /** @var CombinedPriceListActivationPlanBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $combinedPriceListBuilder;

    /** @var BuildCombinedPriceLists */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->combinedPriceListBuilder = $this->createMock(CombinedPriceListActivationPlanBuilder::class);

        $this->processor = new BuildCombinedPriceLists(
            $this->combinedPriceListBuilder
        );
    }

    public function testProcessWhenNoPriceListSchedulesInContext()
    {
        $this->combinedPriceListBuilder->expects(self::never())
            ->method('buildByPriceList');

        $this->processor->process($this->context);
    }

    public function testProcessWhenEmptyPriceListSchedulesInContext()
    {
        $this->combinedPriceListBuilder->expects(self::never())
            ->method('buildByPriceList');

        $this->context->set(SavePriceListSchedulesToContext::PRICE_LIST_SCHEDULES, []);
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $priceLists = [
            $this->createPriceList(1),
            $this->createPriceList(1),
            $this->createPriceList(2)
        ];
        $schedules = [
            (new PriceListSchedule())->setPriceList($priceLists[0]),
            (new PriceListSchedule())->setPriceList($priceLists[1]),
            (new PriceListSchedule())->setPriceList($priceLists[2])
        ];

        $this->combinedPriceListBuilder->expects(self::exactly(2))
            ->method('buildByPriceList')
            ->withConsecutive(
                [$priceLists[0]],
                [$priceLists[2]]
            );

        $this->context->set(SavePriceListSchedulesToContext::PRICE_LIST_SCHEDULES, $schedules);
        $this->processor->process($this->context);
    }

    /**
     * @param int $id
     *
     * @return PriceList|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createPriceList(int $id)
    {
        $priceList = $this->createMock(PriceList::class);
        $priceList->expects(self::any())
            ->method('getId')
            ->willReturn($id);

        return $priceList;
    }
}
