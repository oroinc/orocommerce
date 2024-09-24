<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Reader;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Reader\IteratorBasedReader;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use Oro\Bundle\PricingBundle\ImportExport\Reader\Iterator\AdditionalProductPricesIterator;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

/**
 * Reads additional product prices for price list
 */
class PriceListAdditionalProductPriceReader extends IteratorBasedReader
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
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(
        ContextRegistry $contextRegistry,
        ManagerRegistry $registry,
        ShardManager $shardManager
    ) {
        parent::__construct($contextRegistry);

        $this->registry = $registry;
        $this->shardManager = $shardManager;
    }

    #[\Override]
    protected function initializeFromContext(ContextInterface $context)
    {
        $this->priceListId = (int)$context->getOption('price_list_id');
        $this->setSourceIterator($this->createIterator());

        $jobInstance = $this->stepExecution->getJobExecution()->getJobInstance();
        $configuration = $jobInstance->getRawConfiguration();

        if ($configuration['export']['hasHeader']) {
            $configuration['export']['firstLineIsHeader'] = false;
        }

        unset($configuration['export']['hasHeader']);
        $jobInstance->setRawConfiguration($configuration);

        parent::initializeFromContext($context);
    }

    /**
     * @return \Iterator
     */
    protected function createIterator()
    {
        if ($this->priceListId) {
            /** @var EntityManager $em */
            $em = $this->registry->getManagerForClass(PriceListToProduct::class);

            /** @var PriceListToProductRepository $repository */
            $repository = $em->getRepository(PriceListToProduct::class);

            /** @var PriceList $priceList */
            $priceList = $em->getReference(PriceList::class, $this->priceListId);

            return new AdditionalProductPricesIterator(
                $repository->getProductsWithoutPrices($this->shardManager, $priceList),
                $priceList
            );
        } else {
            return new \ArrayIterator();
        }
    }
}
