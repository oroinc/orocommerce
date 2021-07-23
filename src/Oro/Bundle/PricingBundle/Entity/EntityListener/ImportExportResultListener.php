<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
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
class ImportExportResultListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var PriceRuleLexemeTriggerHandler */
    private $lexemeTriggerHandler;

    /** @var PriceListTriggerHandler */
    private $priceListTriggerHandler;

    /** @var ShardManager */
    private $shardManager;

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

    public function postPersist(ImportExportResult $importExportResult)
    {
        $options = $importExportResult->getOptions();
        if (array_key_exists('price_list_id', $options)) {
            $version = $options['importVersion'] ?? null;
            $priceList = $this->doctrine
                ->getManagerForClass(PriceList::class)
                ->find(PriceList::class, $options['price_list_id']);
            if ($priceList !== null) {
                $this->handlePriceListPricesMassUpdate($priceList, $version);
            }
        }
    }

    private function handlePriceListPricesMassUpdate(PriceList $priceList, ?int $version = null)
    {
        $lexemes = $this->lexemeTriggerHandler->findEntityLexemes(
            PriceList::class,
            ['prices'],
            $priceList->getId()
        );

        foreach ($this->getProductBatches($priceList, $version) as $products) {
            $this->lexemeTriggerHandler->processLexemes($lexemes, $products);
            $this->emitCplTriggers($priceList, $products);
        }
    }

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

    private function getProductPriceRepository(): ProductPriceRepository
    {
        return $this->doctrine->getRepository(ProductPrice::class);
    }

    private function emitCplTriggers(PriceList $priceList, array $products): void
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $this->priceListTriggerHandler->handlePriceListTopic(
            Topics::RESOLVE_COMBINED_PRICES,
            $priceList,
            $products
        );
    }
}
