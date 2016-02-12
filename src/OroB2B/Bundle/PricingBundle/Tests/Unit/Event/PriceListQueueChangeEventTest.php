<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Event;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Event\PriceListQueueChangeEvent;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

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
