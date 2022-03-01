<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event\CombinedPriceList\Assignment;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByConfigEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByCustomerEvent;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class CollectByCustomerEventTest extends TestCase
{
    use EntityTrait;

    public function testAddCustomerAssociation()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $customer = $this->getEntity(Customer::class, ['id' => 10]);

        $event = new CollectByCustomerEvent($website, $customer);
        $this->assertSame($website, $event->getWebsite());
        $this->assertSame($customer, $event->getCustomer());

        $event->addCustomerAssociation(
            [
                'identifier' => 'abcdef',
                'elements' => [['p' => 1, 'm' => true]]
            ],
            $website,
            $customer
        );
        $this->assertEquals(
            [
                'abcdef' => [
                    'collection' => [['p' => 1, 'm' => true]],
                    'assign_to' => ['website' => ['id:1' => ['customer' => ['ids' => [10]]]]]
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
                            'id:1' => ['customer' => ['ids' => [10]]]
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
        $customer1 = $this->getEntity(Customer::class, ['id' => 10]);
        $customer2 = $this->getEntity(Customer::class, ['id' => 20]);

        $event = new CollectByConfigEvent();


        $event2 = new CollectByCustomerEvent($website, $customer1);
        $event2->addCustomerAssociation(
            [
                'identifier' => 'abcdef',
                'elements' => [['p' => 1, 'm' => true]]
            ],
            $website,
            $customer1
        );
        $event->mergeAssociations($event2);

        $event3 = new CollectByCustomerEvent($website, $customer2);
        $event3->addCustomerAssociation(
            [
                'identifier' => 'abcdef',
                'elements' => [['p' => 1, 'm' => true]]
            ],
            $website,
            $customer2
        );
        $event3->addCustomerAssociation(
            [
                'identifier' => 'test2',
                'elements' => [['p' => 2, 'm' => true]]
            ],
            $website,
            $customer2
        );

        $event->mergeAssociations($event3);

        $this->assertEquals(
            [
                'abcdef' => [
                    'collection' => [['p' => 1, 'm' => true]],
                    'assign_to' => [
                        'website' => [
                            'id:1' => [
                                'customer' => ['ids' => [10, 20]]
                            ]
                        ]
                    ]
                ],
                'test2' => [
                    'collection' => [['p' => 2, 'm' => true]],
                    'assign_to' => [
                        'website' => [
                            'id:1' => [
                                'customer' => ['ids' => [20]]
                            ]
                        ]
                    ]
                ]
            ],
            $event->getCombinedPriceListAssociations()
        );
    }
}
