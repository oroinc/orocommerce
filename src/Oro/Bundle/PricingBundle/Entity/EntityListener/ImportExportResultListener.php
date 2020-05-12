<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

/**
 * Resolve combined prices on ImportExportResult post persist
 */
class ImportExportResultListener
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var PriceRuleLexemeTriggerHandler */
    private $lexemeTriggerHandler;

    /** @var PriceListTriggerHandler */
    private $priceListTriggerHandler;

    /** @var ShardManager */
    private $shardManager;

    /**
     * @param ManagerRegistry               $doctrine
     * @param PriceRuleLexemeTriggerHandler $lexemeTriggerHandler
     * @param PriceListTriggerHandler       $priceListTriggerHandler
     * @param ShardManager                  $shardManager
     */
    public function __construct(
        ManagerRegistry $doctrine,
        PriceRuleLexemeTriggerHandler $lexemeTriggerHandler,
        PriceListTriggerHandler $priceListTriggerHandler,
        ShardManager $shardManager
    ) {
        $this->doctrine = $doctrine;
        $this->lexemeTriggerHandler = $lexemeTriggerHandler;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
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
            $priceList = $this->doctrine
                ->getManagerForClass(PriceList::class)
                ->find(PriceList::class, $options['price_list_id']);
            if ($priceList !== null) {
                $this->resolveCombinedPrices($priceList, $version);
            }
        }
    }

    /**
     * @param PriceList $priceList
     * @param int|null  $version
     */
    private function resolveCombinedPrices(PriceList $priceList, ?int $version = null)
    {
        $lexemes = $this->lexemeTriggerHandler->findEntityLexemes(
            PriceList::class,
            ['prices'],
            $priceList->getId()
        );
        foreach ($this->getProductBatches($priceList, $version) as $products) {
            $this->lexemeTriggerHandler->processLexemes($lexemes, $products);
            $this->priceListTriggerHandler->handlePriceListTopic(
                Topics::RESOLVE_COMBINED_PRICES,
                $priceList,
                $products
            );
        }
    }

    /**
     * @param PriceList $priceList
     * @param int|null  $version
     *
     * @return iterable
     */
    private function getProductBatches(PriceList $priceList, ?int $version = null): iterable
    {
        if (!$version) {
            yield [];
        } else {
            yield from $this->getProductPriceRepository()->getProductsByPriceListAndVersion(
                $this->shardManager,
                $priceList,
                $version
            );
        }
    }

    /**
     * @return ProductPriceRepository
     */
    private function getProductPriceRepository(): ProductPriceRepository
    {
        return $this->doctrine->getRepository(ProductPrice::class);
    }
}
