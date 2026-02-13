<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\PricingBundle\Async\Topic\GenerateDependentPriceListPricesTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
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
    private PriceListRelationTriggerHandler $priceListRelationTriggerHandler;

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

    public function setPriceListRelationTriggerHandler(PriceListRelationTriggerHandler $handler): void
    {
        $this->priceListRelationTriggerHandler = $handler;
    }

    public function postPersist(ImportExportResult $importExportResult)
    {
        if (!$this->isSupported($importExportResult)) {
            return;
        }

        $type = $importExportResult->getType();
        $options = $importExportResult->getOptions();
        $priceListId = $options['price_list_id'];
        $version = $options['importVersion'];
        $priceList = $this->doctrine
            ->getManagerForClass(PriceList::class)
            ?->find(PriceList::class, $priceListId);

        if ($priceList !== null && $type !== ProcessorRegistry::TYPE_IMPORT_VALIDATION) {
            $this->producer->send(
                GenerateDependentPriceListPricesTopic::getName(),
                [
                    'sourcePriceListId' => $priceList->getId(),
                    'version' => $version
                ]
            );
        }
    }

    private function isSupported(ImportExportResult $importExportResult): bool
    {
        $options = $importExportResult->getOptions();

        return isset($options['price_list_id']) && isset($options['importVersion']);
    }
}
