<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Reader;

use Doctrine\ORM\Query;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Reader\EntityReader;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalker;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

class PriceListProductPricesReader extends EntityReader
{
    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @var int
     */
    protected $priceListId;

    /**
     * {@inheritdoc}
     */
    protected function createSourceEntityQueryBuilder($entityName, Organization $organization = null, array $ids = [])
    {
        $qb = parent::createSourceEntityQueryBuilder($entityName, $organization, $ids);

        if ($this->priceListId) {
            $aliases = $qb->getRootAliases();
            $rootAlias = reset($aliases);
            $qb
                ->andWhere(
                    $qb->expr()->eq(sprintf('IDENTITY(%s.priceList)', $rootAlias), ':priceList')
                )
                ->setParameter('priceList', $this->priceListId);
        }

        return $qb;
    }

    /**
     * @param Query $query
     */
    public function setSourceQuery(Query $query)
    {
        $query->useQueryCache(false);
        $query->setHint(PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $this->shardManager);
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);
        $this->setSourceIterator($this->createSourceIterator($query));
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        $this->priceListId = (int)$context->getOption('price_list_id');

        parent::initializeFromContext($context);
    }

    /**
     * @param ShardManager $shardManager
     */
    public function setShardManager(ShardManager $shardManager)
    {
        $this->shardManager = $shardManager;
    }
}
