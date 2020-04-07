<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

/**
 * Resolve combined prices on ImportExportResult post persist
 */
class ImportExportResultListener
{
    /** @var ManagerRegistry */
    private $registry;

    /** @var PriceRuleLexemeTriggerHandler */
    private $lexemeTriggerHandler;

    /** @var PriceListTriggerHandler */
    private $priceListTriggerHandler;

    /** @var ShardManager */
    private $shardManager;

    /**
     * @param ManagerRegistry $registry
     * @param PriceRuleLexemeTriggerHandler $lexemeTriggerHandler
     * @param PriceListTriggerHandler $priceListTriggerHandler
     */
    public function __construct(
        ManagerRegistry $registry,
        PriceRuleLexemeTriggerHandler $lexemeTriggerHandler,
        PriceListTriggerHandler $priceListTriggerHandler
    ) {
        $this->registry = $registry;
        $this->lexemeTriggerHandler = $lexemeTriggerHandler;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
    }

    /**
     * @param ShardManager $shardManager
     */
    public function setShardManager(ShardManager $shardManager)
    {
        $this->shardManager = $shardManager;
    }

    /**
     * @param ImportExportResult $importExportResult
     */
    public function postPersist(ImportExportResult $importExportResult)
    {
        $options = $importExportResult->getOptions();
        if (array_key_exists('price_list_id', $options)) {
            $version = $options['importVersion'] ?? null;
            $priceList = $this->registry
                ->getManagerForClass(PriceList::class)
                ->find(PriceList::class, $options['price_list_id']);
            if ($priceList !== null) {
                $this->resolveCombinedPrices($priceList, $version);
            }
        }
    }

    /**
     * @param PriceList $priceList
     * @param int|null $version
     */
    private function resolveCombinedPrices(PriceList $priceList, ?int $version = null)
    {
        $lexemes = $this->lexemeTriggerHandler->findEntityLexemes(PriceList::class, ['prices'], $priceList->getId());
        foreach ($this->getProductBatches($priceList, $version) as $products) {
            $this->lexemeTriggerHandler->addTriggersByLexemes($lexemes, $products);
            $this->priceListTriggerHandler->addTriggerForPriceList(
                Topics::RESOLVE_COMBINED_PRICES,
                $priceList,
                $products
            );
            $this->priceListTriggerHandler->sendScheduledTriggers();
        }
    }

    /**
     * @param PriceList $priceList
     * @param int|null $version
     * @return \Generator
     */
    private function getProductBatches(PriceList $priceList, ?int $version = null)
    {
        if (!$version) {
            yield [];
        } else {
            $repository = $this->registry
                ->getManagerForClass(ProductPrice::class)
                ->getRepository(ProductPrice::class);

            yield from $repository->getProductsByPriceListAndVersion(
                $this->shardManager,
                $priceList,
                $version,
                PriceListTriggerHandler::BATCH_SIZE
            );
        }
    }
}
