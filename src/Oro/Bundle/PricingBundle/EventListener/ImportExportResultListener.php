<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\ImportExportBundle\Event\PostHttpImportEvent;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Resolve combined prices after http import was finished
 */
class ImportExportResultListener
{
    /** @var RegistryInterface */
    private $registry;

    /** @var PriceRuleLexemeTriggerHandler */
    private $lexemeTriggerHandler;

    /** @var PriceListTriggerHandler */
    private $priceListTriggerHandler;

    /**
     * @param RegistryInterface $registry
     * @param PriceRuleLexemeTriggerHandler $lexemeTriggerHandler
     * @param PriceListTriggerHandler $priceListTriggerHandler
     */
    public function __construct(
        RegistryInterface $registry,
        PriceRuleLexemeTriggerHandler $lexemeTriggerHandler,
        PriceListTriggerHandler $priceListTriggerHandler
    ) {
        $this->registry = $registry;
        $this->lexemeTriggerHandler = $lexemeTriggerHandler;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
    }

    /**
     * @param PostHttpImportEvent $event
     */
    public function postHttpImport(PostHttpImportEvent $event)
    {
        $options = $event->getOptions();
        if (array_key_exists('price_list_id', $options)) {
            $priceList = $this->registry
                ->getManagerForClass(PriceList::class)
                ->find(PriceList::class, $options['price_list_id']);
            if ($priceList !== null) {
                $this->resolveCombinedPrices($priceList);
            }
        }
    }

    /**
     * @param PriceList $priceList
     */
    private function resolveCombinedPrices(PriceList $priceList)
    {
        $lexemes = $this->lexemeTriggerHandler->findEntityLexemes(PriceList::class, ['prices'], $priceList->getId());
        $this->lexemeTriggerHandler->addTriggersByLexemes($lexemes);
        $this->priceListTriggerHandler->addTriggerForPriceList(Topics::RESOLVE_COMBINED_PRICES, $priceList);
        $this->priceListTriggerHandler->sendScheduledTriggers();
    }
}
