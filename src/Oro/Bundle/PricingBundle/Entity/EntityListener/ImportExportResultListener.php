<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceByPriceListTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveFlatPriceTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Resolve prices on ImportExportResult post persist.
 */
class ImportExportResultListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    private ManagerRegistry $doctrine;
    private PriceRuleLexemeTriggerHandler $lexemeTriggerHandler;
    private PriceListTriggerHandler $priceListTriggerHandler;
    private ShardManager $shardManager;
    private MessageProducerInterface $producer;

    public function __construct(
        ManagerRegistry $doctrine,
        PriceRuleLexemeTriggerHandler $lexemeTriggerHandler,
        PriceListTriggerHandler $priceListTriggerHandler,
        ShardManager $shardManager,
        MessageProducerInterface $producer
    ) {
        $this->doctrine = $doctrine;
        $this->lexemeTriggerHandler = $lexemeTriggerHandler;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
        $this->shardManager = $shardManager;
        $this->producer = $producer;
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

            if ($this->isFeaturesEnabled()) {
                $this->emitCplTriggers($priceList, $products);
            } else {
                $this->emitFlatTriggers($priceList, $products);
            }
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
        $this->priceListTriggerHandler->handlePriceListTopic(
            ResolveCombinedPriceByPriceListTopic::getName(),
            $priceList,
            $products
        );
    }

    private function emitFlatTriggers(PriceList $priceList, array $products): void
    {
        $this->producer->send(
            ResolveFlatPriceTopic::getName(),
            ['priceList' => $priceList->getId(), 'products' => $products]
        );
    }
}
