<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event for processing combined price list associations.
 */
class ProcessEvent extends Event
{
    public const NAME = 'oro_pricing.combined_price_list.assignment.process';

    private CombinedPriceList $combinedPriceList;
    private array $associations;
    private bool $skipUpdateNotification;
    private ?int $version = null;

    public function __construct(
        CombinedPriceList $combinedPriceList,
        array $associations,
        ?int $version,
        bool $skipUpdateNotification = false,
    ) {
        $this->associations = $associations;
        $this->combinedPriceList = $combinedPriceList;
        $this->version = $version;
        $this->skipUpdateNotification = $skipUpdateNotification;
    }

    public function getCombinedPriceList(): CombinedPriceList
    {
        return $this->combinedPriceList;
    }

    public function getAssociations(): array
    {
        return $this->associations;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function isSkipUpdateNotification(): bool
    {
        return $this->skipUpdateNotification;
    }
}
