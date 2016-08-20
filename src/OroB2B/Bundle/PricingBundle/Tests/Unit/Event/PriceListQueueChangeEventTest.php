<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\PricingBundle\Event\PriceListQueueChangeEvent;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class PriceListQueueChangeEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $account = new Account();
        $website = new Website();
        $event = new PriceListQueueChangeEvent($account, $website);
        $this->assertEquals($event->getTargetEntity(), $account);
        $this->assertEquals($event->getWebsite(), $website);
    }
}
