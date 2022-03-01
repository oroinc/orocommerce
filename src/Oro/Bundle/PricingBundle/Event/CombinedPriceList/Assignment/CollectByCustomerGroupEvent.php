<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Event for collecting Price list collections by Website-Customer Group pair
 */
class CollectByCustomerGroupEvent extends CollectByWebsiteEvent
{
    public const NAME = 'oro_pricing.combined_price_list.assignment.collect.by_customer_group';
    private CustomerGroup $customerGroup;

    public function __construct(
        Website $website,
        CustomerGroup $customerGroup,
        bool $includeSelfFallback = false,
        bool $collectOnCurrentLevel = true
    ) {
        parent::__construct($website, $includeSelfFallback, $collectOnCurrentLevel);
        $this->customerGroup = $customerGroup;
    }

    public function getCustomerGroup(): CustomerGroup
    {
        return $this->customerGroup;
    }

    public function addCustomerGroupAssociation(
        array $collectionInfo,
        Website $website,
        CustomerGroup $customerGroup
    ): void {
        $this->addAssociation(
            $collectionInfo,
            [
                'website' => [
                    'id:' . $website->getId() => [
                        'customer_group' => [
                            'ids' => [$customerGroup->getId()]
                        ]
                    ]
                ]
            ]
        );
    }
}
