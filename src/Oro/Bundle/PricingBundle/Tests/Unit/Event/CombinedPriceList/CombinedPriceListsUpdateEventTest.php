<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event\CombinedPriceList;

use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListsUpdateEvent;

class CombinedPriceListsUpdateEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var integer[]
     */
    protected $cplIds;

    /**
     * @var CombinedPriceListsUpdateEvent
     */
    protected $combinedPriceListsUpdateEvent;

    protected function setUp(): void
    {
        $this->cplIds = [1, 2, 3];
        $this->combinedPriceListsUpdateEvent = new CombinedPriceListsUpdateEvent($this->cplIds);
    }

    public function testGetCombinedPriceListIds()
    {
        $this->assertEquals($this->combinedPriceListsUpdateEvent->getCombinedPriceListIds(), $this->cplIds);
    }
}
