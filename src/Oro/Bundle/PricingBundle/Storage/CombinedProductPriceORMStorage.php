<?php

namespace Oro\Bundle\PricingBundle\Storage;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\BaseProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

/**
 * Fetch prices from Combined Price Lists DB storage.
 */
class CombinedProductPriceORMStorage extends AbstractProductPriceORMStorage
{
    /**
     * @var CombinedPriceListTreeHandler
     */
    private $priceListTreeHandler;

    public function __construct(
        ManagerRegistry $registry,
        ShardManager $shardManager,
        CombinedPriceListTreeHandler $priceListTreeHandler
    ) {
        parent::__construct($registry, $shardManager);
        $this->priceListTreeHandler = $priceListTreeHandler;
    }

    /**
     * {@inheritDoc}
     */
    protected function getRepository(): BaseProductPriceRepository
    {
        return $this->registry
            ->getManagerForClass(CombinedProductPrice::class)
            ->getRepository(CombinedProductPrice::class);
    }

    /**
     * {@inheritDoc}
     */
    protected function getPriceListByScopeCriteria(ProductPriceScopeCriteriaInterface $scopeCriteria)
    {
        return $this->priceListTreeHandler->getPriceList($scopeCriteria->getCustomer(), $scopeCriteria->getWebsite());
    }
}
