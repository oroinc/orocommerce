<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event\CombinedPriceList\Assignment;

use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByConfigEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByWebsiteEvent;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class CollectByWebsiteEventTest extends TestCase
{
    use EntityTrait;

    public function testAddWebsiteAssociation()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $event = new CollectByWebsiteEvent($website);
        $this->assertSame($website, $event->getWebsite());

        $event->addWebsiteAssociation(
            [
                'identifier' => 'abcdef',
                'elements' => [['p' => 1, 'm' => true]]
            ],
            $website
        );
        $this->assertEquals(
            [
                'abcdef' => [
                    'collection' => [['p' => 1, 'm' => true]],
                    'assign_to' => ['website' => ['ids' => [1]]]
                ]
            ],
            $event->getCombinedPriceListAssociations()
        );
    }

    public function testMergeAssociation()
    {
        $website1 = $this->getEntity(Website::class, ['id' => 1]);
        $website2 = $this->getEntity(Website::class, ['id' => 2]);
        $event = new CollectByConfigEvent();
        $event->addAssociation(
            [
                'identifier' => 'abcdef',
                'elements' => [['p' => 1, 'm' => true]]
            ],
            ['config' => true]
        );

        $event2 = new CollectByWebsiteEvent($website1);
        $event2->addWebsiteAssociation(
            [
                'identifier' => 'abcdef',
                'elements' => [['p' => 1, 'm' => true]]
            ],
            $website1
        );
        $event->mergeAssociations($event2);

        $event3 = new CollectByWebsiteEvent($website2);
        $event3->addWebsiteAssociation(
            [
                'identifier' => 'abcdef',
                'elements' => [['p' => 1, 'm' => true]]
            ],
            $website2
        );

        $event->mergeAssociations($event3);

        $this->assertEquals(
            [
                'abcdef' => [
                    'collection' => [['p' => 1, 'm' => true]],
                    'assign_to' => [
                        'config' => true,
                        'website' => [
                            'ids' => [1, 2]
                        ]
                    ]
                ]
            ],
            $event->getCombinedPriceListAssociations()
        );
    }
}
