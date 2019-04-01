<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Reader;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Reader\EntityReader;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalker;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

/**
 * Prepares the list of entities for export.
 * Responsible for creating a list for each batch during export
 */
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

    /**
     * @param ObjectManager $entityManager
     * @param string $entityName
     * @param array $options
     *
     * @return QueryBuilder
     */
    protected function createQueryBuilderByEntityNameAndIdentifier(
        ObjectManager $entityManager,
        string $entityName,
        array $options = []
    ): QueryBuilder {
        if (!array_key_exists('price_list_id', $options)) {
            throw new \LogicException('Unable to read prices, price_list_id should be defined.');
        }

        $queryBuilder = parent::createQueryBuilderByEntityNameAndIdentifier(
            $entityManager,
            $entityName,
            $options
        );
        $queryBuilder->andWhere('o.priceList = :priceListId');
        $queryBuilder->setParameter('priceListId', $options['price_list_id']);

        return $queryBuilder;
    }
}
