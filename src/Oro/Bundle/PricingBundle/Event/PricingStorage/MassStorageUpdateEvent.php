<?php

namespace Oro\Bundle\PricingBundle\Event\PricingStorage;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Mass pricing storage (Price List) update.
 */
class MassStorageUpdateEvent extends Event
{
    public const NAME = 'oro_pricing.pricing_storage.mass_storage_update';

    /**
     * @var array
     */
    protected $priceListIds;

    /**
     * @param array $ids
     */
    public function __construct(array $ids)
    {
        $this->priceListIds = $ids;
    }

    /**
     * @return array
     */
    public function getPriceListIds(): array
    {
        return $this->priceListIds;
    }
}
