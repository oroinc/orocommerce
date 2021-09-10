<?php

namespace Oro\Bundle\PricingBundle\Event\PricingStorage;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when websites to pricing storage (Price List) relation is updated.
 */
class WebsiteRelationUpdateEvent extends Event
{
    public const NAME = 'oro_pricing.pricing_storage.website_relation_update';

    /**
     * @var array
     */
    protected $websiteIds;

    public function __construct(array $websiteIds)
    {
        $this->websiteIds = $websiteIds;
    }

    public function getWebsiteIds(): array
    {
        return $this->websiteIds;
    }
}
