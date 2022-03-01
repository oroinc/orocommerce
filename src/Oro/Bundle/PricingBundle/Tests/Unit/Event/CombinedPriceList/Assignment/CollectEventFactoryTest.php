<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event\CombinedPriceList\Assignment;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByConfigEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByCustomerEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByCustomerGroupEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByWebsiteEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectEventFactory;
use Oro\Bundle\PricingBundle\Exception\UnknownTargetEntityException;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class CollectEventFactoryTest extends TestCase
{
    use EntityTrait;

    /**
     * @dataProvider eventDataProvider
     */
    public function testCreateEvent(
        CollectByConfigEvent $expectedEvent,
        bool $force = false,
        Website $website = null,
        object $targetEntity = null
    ) {
        $factory = new CollectEventFactory();
        $event = $factory->createEvent($force, $website, $targetEntity);

        $this->assertEquals($expectedEvent, $event);
    }

    public function eventDataProvider(): \Generator
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 10]);
        $customer = $this->getEntity(Customer::class, ['id' => 100]);

        yield [
            new CollectByConfigEvent(true),
            true
        ];

        yield [
            new CollectByConfigEvent(false),
            false
        ];

        yield [
            new CollectByWebsiteEvent($website, true),
            true,
            $website
        ];

        yield [
            new CollectByWebsiteEvent($website, false),
            false,
            $website
        ];

        yield [
            new CollectByCustomerGroupEvent($website, $customerGroup, true),
            true,
            $website,
            $customerGroup
        ];

        yield [
            new CollectByCustomerGroupEvent($website, $customerGroup, false),
            false,
            $website,
            $customerGroup
        ];

        yield [
            new CollectByCustomerEvent($website, $customer, true),
            true,
            $website,
            $customer
        ];

        yield [
            new CollectByCustomerEvent($website, $customer, false),
            false,
            $website,
            $customer
        ];
    }

    public function testCreateEventUnsupportedTarget()
    {
        $this->expectException(UnknownTargetEntityException::class);

        $factory = new CollectEventFactory();
        $factory->createEvent(true, $this->getEntity(Website::class, ['id' => 1]), new User());
    }
}
