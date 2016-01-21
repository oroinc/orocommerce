<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Event;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Event\PriceListCollectionChange;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class PriceListCollectionChangeTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $account = new Account();
        $website = new Website();
        $event = new PriceListCollectionChange($account, $website);
        $this->assertEquals($event->getTargetEntity(), $account);
        $this->assertEquals($event->getWebsite(), $website);
    }
}
