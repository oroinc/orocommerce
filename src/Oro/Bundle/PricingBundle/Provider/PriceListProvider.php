<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceList;

/**
 * Provides a default price list entity.
 */
class PriceListProvider
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function getDefaultPriceList(): ?PriceList
    {
        return $this->doctrine->getRepository(PriceList::class)
            ->findOneBy(['default' => true]);
    }

    public function getDefaultPriceListId(): ?int
    {
        return $this->getDefaultPriceList()?->getId();
    }
}
