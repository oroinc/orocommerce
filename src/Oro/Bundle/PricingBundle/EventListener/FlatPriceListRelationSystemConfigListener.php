<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveFlatPriceTopic;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Handler\PriceListRelationHandler;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Responsible for updating the product price index when changing system config relations.
 */
class FlatPriceListRelationSystemConfigListener implements OptionalListenerInterface, FeatureToggleableInterface
{
    use OptionalListenerTrait, FeatureCheckerHolderTrait;

    private const DEFAULT_PRICE_LIST_KEY = Configuration::ROOT_NODE . '.' . Configuration::DEFAULT_PRICE_LIST;

    private PriceListRelationHandler $priceListRelationHandler;
    private MessageProducerInterface $producer;
    private ManagerRegistry $doctrine;

    public function __construct(
        PriceListRelationHandler $priceListRelationHandler,
        MessageProducerInterface $producer,
        ManagerRegistry $doctrine
    ) {
        $this->priceListRelationHandler = $priceListRelationHandler;
        $this->producer = $producer;
        $this->doctrine = $doctrine;
    }

    public function beforeSave(ConfigSettingsUpdateEvent $event): void
    {
        if (!$this->isSupported()) {
            return;
        }

        $settings = $event->getSettings();
        $priceListId = $settings['value'] ?? null;
        if ($priceListId) {
            $priceList = $this->doctrine->getManager()->find(PriceList::class, $priceListId);
            if ($priceList && !$this->priceListRelationHandler->isPriceListAlreadyUsed($priceList)) {
                $this->producer->send(ResolveFlatPriceTopic::getName(), ['priceList' => $priceList->getId()]);
            }
        }
    }

    private function isSupported(): bool
    {
        return $this->enabled && $this->isFeaturesEnabled();
    }
}
