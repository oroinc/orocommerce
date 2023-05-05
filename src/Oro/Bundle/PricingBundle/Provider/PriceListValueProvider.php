<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Provides first price list or null depending on sharding state.
 *
 * Needed for datagrid filters, e.g. see priceListName in product-prices-grid
 */
class PriceListValueProvider
{
    private ShardManager $shardManager;
    private ManagerRegistry $doctrine;
    private AclHelper $aclHelper;

    public function __construct(ShardManager $shardManager, ManagerRegistry $doctrine, AclHelper $aclHelper)
    {
        $this->shardManager = $shardManager;
        $this->doctrine = $doctrine;
        $this->aclHelper = $aclHelper;
    }

    public function getPriceListId(): ?int
    {
        if ($this->shardManager->isShardingEnabled()) {
            $qb = $this->doctrine->getRepository(PriceList::class)
                ->createQueryBuilder('p.id')
                ->orderBy('p.id')
                ->setMaxResults(1);

            return $this->aclHelper->apply($qb)->getSingleScalarResult();
        }

        return null;
    }
}
