<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Event\CombinedPriceList;

use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\AccountCPLUpdateEvent;

class AccountCPLUpdateEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $data = [
            'websiteId' => 1,
            'accountIds' => [1, 2, 3]
        ];
        $event = new AccountCPLUpdateEvent($data);
        $this->assertInstanceOf('Symfony\Component\EventDispatcher\Event', $event);
        $this->assertSame($data, $event->getAccountsData());
    }
}
