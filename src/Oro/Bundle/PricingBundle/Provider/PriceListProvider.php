<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\PricingBundle\Entity\PriceList;

class PriceListProvider
{
    /**
     * @var Registry
     */
    protected $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @return PriceList
     */
    public function getDefaultPriceList()
    {
        return $this->registry
            ->getManagerForClass(PriceList::class)
            ->getRepository(PriceList::class)
            ->findOneBy(['default' => true]);
    }

    /**
     * @return int
     */
    public function getDefaultPriceListId()
    {
        return $this->getDefaultPriceList()->getId();
    }
}
