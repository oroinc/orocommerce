<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Event for collecting Price list collections by Website-Customer pair
 */
class CollectByCustomerEvent extends CollectByWebsiteEvent
{
    public const NAME = 'oro_pricing.combined_price_list.assignment.collect.by_customer';
    private Customer $customer;

    public function __construct(
        Website $website,
        Customer $customer,
        bool $includeSelfFallback = false,
        bool $collectOnCurrentLevel = true
    ) {
        parent::__construct($website, $includeSelfFallback, $collectOnCurrentLevel);
        $this->customer = $customer;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function addCustomerAssociation(array $collectionInfo, Website $website, Customer $customer): void
    {
        $this->addAssociation(
            $collectionInfo,
            [
                'website' => [
                    'id:' . $website->getId() => [
                        'customer' => [
                            'ids' => [$customer->getId()]
                        ]
                    ]
                ]
            ]
        );
    }
}
