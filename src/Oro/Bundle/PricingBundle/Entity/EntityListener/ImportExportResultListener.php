<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceByVersionedPriceListTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveVersionedFlatPriceTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
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
    private ShardManager $shardManager;
    private MessageProducerInterface $producer;

    public function __construct(
        ManagerRegistry $doctrine,
        PriceRuleLexemeTriggerHandler $lexemeTriggerHandler,
        ShardManager $shardManager,
        MessageProducerInterface $producer
    ) {
        $this->doctrine = $doctrine;
        $this->lexemeTriggerHandler = $lexemeTriggerHandler;
        $this->shardManager = $shardManager;
        $this->producer = $producer;
    }

    public function postPersist(ImportExportResult $importExportResult)
    {
        if (!$this->isSupported($importExportResult)) {
            return;
        }

        $entityManager = $this->doctrine->getManagerForClass(PriceList::class);
        $type = $importExportResult->getType();
        $options = $importExportResult->getOptions();
        $priceListId = $options['price_list_id'];
        $version = $options['importVersion'];
        $priceList = $entityManager->find(PriceList::class, $priceListId);
        if ($priceList !== null && $type !== ProcessorRegistry::TYPE_IMPORT_VALIDATION) {
            $this->processLexemes($priceList, $version);
            if ($this->isFeaturesEnabled()) {
                $this->emitCplTriggers($priceList, $version);
            } else {
                $this->emitFlatTriggers($priceList, $version);
            }
        }
    }

    private function processLexemes(PriceList $priceList, ?int $version = null): void
    {
        $lexemes = $this->lexemeTriggerHandler->findEntityLexemes(
            PriceList::class,
            ['prices'],
            $priceList->getId()
        );

        foreach ($this->getProductBatches($priceList, $version) as $products) {
            $this->lexemeTriggerHandler->processLexemes($lexemes, $products);
        }
    }

    private function getProductBatches(PriceList $priceList, ?int $version = null): iterable
    {
        if (!$version) {
            yield [];
        } else {
            yield from $this->getProductPriceRepository()->getProductsByPriceListAndVersion(
                $this->shardManager,
                $priceList->getId(),
                $version
            );
        }
    }

    private function isSupported(ImportExportResult $importExportResult): bool
    {
        $options = $importExportResult->getOptions();

        return isset($options['price_list_id']) && isset($options['importVersion']);
    }

    private function getProductPriceRepository(): ProductPriceRepository
    {
        return $this->doctrine->getRepository(ProductPrice::class);
    }

    private function emitCplTriggers(PriceList $priceList, int $version): void
    {
        $this->producer->send(
            ResolveCombinedPriceByVersionedPriceListTopic::getName(),
            ['priceLists' => [$priceList->getId()], 'version' => $version]
        );
    }

    private function emitFlatTriggers(PriceList $priceList, int $version): void
    {
        $this->producer->send(
            ResolveVersionedFlatPriceTopic::getName(),
            ['priceLists' => [$priceList->getId()], 'version' => $version]
        );
    }
}
