<?php

namespace Oro\Bundle\PricingBundle\Storage;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\BaseProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\FlatPriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

/**
 * Fetch prices from Price Lists DB storage.
 */
class ProductPriceORMStorage extends AbstractProductPriceORMStorage
{
    /**
     * @var FlatPriceListTreeHandler
     */
    private $priceListTreeHandler;

    public function __construct(
        ManagerRegistry $registry,
        ShardManager $shardManager,
        FlatPriceListTreeHandler $priceListTreeHandler
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
            ->getManagerForClass(ProductPrice::class)
            ->getRepository(ProductPrice::class);
    }

    /**
     * {@inheritDoc}
     */
    protected function getPriceListByScopeCriteria(ProductPriceScopeCriteriaInterface $scopeCriteria)
    {
        return $this->priceListTreeHandler->getPriceList($scopeCriteria->getCustomer(), $scopeCriteria->getWebsite());
    }
}
