<?php

namespace Oro\Bundle\PricingBundle\EventListener\CombinedPriceListAssociation;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\GetAssociatedWebsitesEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\ProcessEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\ConfigCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Resolver\ActiveCombinedPriceListResolver;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Processing combined price list associations on config level
 */
class ProcessAssociationConfigEventListener
{
    private EventDispatcherInterface $eventDispatcher;
    private ActiveCombinedPriceListResolver $activeCombinedPriceListResolver;
    private ConfigManager $configManager;
    private WebsiteProviderInterface $websiteProvider;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ActiveCombinedPriceListResolver $activeCombinedPriceListResolver,
        ConfigManager $configManager,
        WebsiteProviderInterface $websiteProvider
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->activeCombinedPriceListResolver = $activeCombinedPriceListResolver;
        $this->configManager = $configManager;
        $this->websiteProvider = $websiteProvider;
    }

    public function onProcessAssociations(ProcessEvent $event): void
    {
        $associations = $event->getAssociations();
        if (empty($associations['config'])) {
            return;
        }

        $hasChanges = $this->processAssignment($event->getCombinedPriceList());

        if ($hasChanges && !$event->isSkipUpdateNotification()) {
            $this->eventDispatcher->dispatch(new ConfigCPLUpdateEvent(), ConfigCPLUpdateEvent::NAME);
        }
    }

    private function processAssignment(CombinedPriceList $cpl): bool
    {
        $activeCpl = $this->activeCombinedPriceListResolver->getActiveCplByFullCPL($cpl);
        $actualCplConfigKey = Configuration::getConfigKeyToPriceList();
        $fullCplConfigKey = Configuration::getConfigKeyToFullPriceList();
        $hasChanged = false;
        if ((int)$this->configManager->get($fullCplConfigKey) !== $cpl->getId()) {
            $this->configManager->set($fullCplConfigKey, $cpl->getId());
            $hasChanged = true;
        }
        if ((int)$this->configManager->get($actualCplConfigKey) !== $activeCpl->getId()) {
            $this->configManager->set($actualCplConfigKey, $activeCpl->getId());
            $hasChanged = true;
        }
        if ($hasChanged) {
            $this->configManager->flush();
        }

        return $hasChanged;
    }

    /**
     * Gather website associations.
     *
     * When CPL is assigned to config - trigger indexation for all websites as config is a base level for all of them
     */
    public function onGetAssociatedWebsites(GetAssociatedWebsitesEvent $event): void
    {
        $associations = $event->getAssociations();
        if ($associations) {
            $websites = $this->getWebsitesByAssociations($associations);
        } else {
            $websites = $this->getWebsitesAssociatedWithCombinedPriceList($event->getCombinedPriceList());
        }

        foreach ($websites as $website) {
            $event->addWebsiteAssociation($website);
        }
    }

    private function getWebsitesByAssociations(array $associations): array
    {
        if (!empty($associations['config'])) {
            return $this->websiteProvider->getWebsites();
        }

        return [];
    }

    private function getWebsitesAssociatedWithCombinedPriceList(CombinedPriceList $combinedPriceList): array
    {
        $actualCplConfigKey = Configuration::getConfigKeyToPriceList();
        if ((int)$this->configManager->get($actualCplConfigKey) === $combinedPriceList->getId()) {
            return $this->websiteProvider->getWebsites();
        }

        return [];
    }
}
