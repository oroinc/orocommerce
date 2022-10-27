<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event\CombinedPriceList;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListCreateEvent;

class CombinedPriceListCreateEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGetCombinedPriceList()
    {
        $combinedPriceList = new CombinedPriceList();

        $event = new CombinedPriceListCreateEvent($combinedPriceList);

        $this->assertSame($combinedPriceList, $event->getCombinedPriceList());
    }

    public function testGetCombinedPriceListWithOptions()
    {
        $combinedPriceList = new CombinedPriceList();
        $options = ['test' => true];

        $event = new CombinedPriceListCreateEvent($combinedPriceList, $options);

        $this->assertSame($combinedPriceList, $event->getCombinedPriceList());
        $this->assertSame($options, $event->getOptions());
    }
}
