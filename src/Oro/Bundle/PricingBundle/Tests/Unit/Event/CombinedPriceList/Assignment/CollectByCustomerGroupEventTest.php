<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event\CombinedPriceList\Assignment;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByConfigEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByCustomerGroupEvent;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class CollectByCustomerGroupEventTest extends TestCase
{
    use EntityTrait;

    public function testAddCustomerGroupAssociation()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 10]);

        $event = new CollectByCustomerGroupEvent($website, $customerGroup);
        $this->assertSame($website, $event->getWebsite());
        $this->assertSame($customerGroup, $event->getCustomerGroup());

        $event->addCustomerGroupAssociation(
            [
                'identifier' => 'abcdef',
                'elements' => [['p' => 1, 'm' => true]]
            ],
            $website,
            $customerGroup
        );
        $this->assertEquals(
            [
                'abcdef' => [
                    'collection' => [['p' => 1, 'm' => true]],
                    'assign_to' => ['website' => ['id:1' => ['customer_group' => ['ids' => [10]]]]]
                ]
            ],
            $event->getCombinedPriceListAssociations()
        );

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
                    'assign_to' => [
                        'website' => [
                            'ids' => [1],
                            'id:1' => ['customer_group' => ['ids' => [10]]]
                        ]
                    ]
                ]
            ],
            $event->getCombinedPriceListAssociations()
        );
    }

    public function testMergeAssociation()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $customerGroup1 = $this->getEntity(CustomerGroup::class, ['id' => 10]);
        $customerGroup2 = $this->getEntity(CustomerGroup::class, ['id' => 20]);

        $event = new CollectByConfigEvent();


        $event2 = new CollectByCustomerGroupEvent($website, $customerGroup1);
        $event2->addCustomerGroupAssociation(
            [
                'identifier' => 'abcdef',
                'elements' => [['p' => 1, 'm' => true]]
            ],
            $website,
            $customerGroup1
        );
        $event->mergeAssociations($event2);

        $event3 = new CollectByCustomerGroupEvent($website, $customerGroup2);
        $event3->addCustomerGroupAssociation(
            [
                'identifier' => 'abcdef',
                'elements' => [['p' => 1, 'm' => true]]
            ],
            $website,
            $customerGroup2
        );
        $event3->addCustomerGroupAssociation(
            [
                'identifier' => 'test2',
                'elements' => [['p' => 2, 'm' => true]]
            ],
            $website,
            $customerGroup2
        );

        $event->mergeAssociations($event3);

        $this->assertEquals(
            [
                'abcdef' => [
                    'collection' => [['p' => 1, 'm' => true]],
                    'assign_to' => [
                        'website' => [
                            'id:1' => [
                                'customer_group' => ['ids' => [10, 20]]
                            ]
                        ]
                    ]
                ],
                'test2' => [
                    'collection' => [['p' => 2, 'm' => true]],
                    'assign_to' => [
                        'website' => [
                            'id:1' => [
                                'customer_group' => ['ids' => [20]]
                            ]
                        ]
                    ]
                ]
            ],
            $event->getCombinedPriceListAssociations()
        );
    }
}
