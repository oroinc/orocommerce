<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Event\CombinedPriceList;

use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\AccountGroupCPLUpdateEvent;

class AccountGroupCPLUpdateEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $data = [
            'websiteId' => 1,
            'accountGroupsIds' => [1, 2, 3]
        ];
        $event = new AccountGroupCPLUpdateEvent($data);
        $this->assertInstanceOf('Symfony\Component\EventDispatcher\Event', $event);
        $this->assertSame($data, $event->getAccountGroupsData());
    }
}
