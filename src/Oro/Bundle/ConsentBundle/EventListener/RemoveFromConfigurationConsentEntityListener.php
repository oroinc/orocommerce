<?php

namespace Oro\Bundle\ConsentBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfigManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Event Listener that cleans config if consent was deleted
 */
class RemoveFromConfigurationConsentEntityListener
{
    private ConsentConfigManager $consentConfigManager;
    private ManagerRegistry $doctrine;

    public function __construct(
        ManagerRegistry $doctrine,
        ConsentConfigManager $consentConfigManager
    ) {
        $this->doctrine = $doctrine;
        $this->consentConfigManager = $consentConfigManager;
    }

    public function preRemove(Consent $consent): void
    {
        $websiteRepository = $this->doctrine
            ->getManagerForClass(Website::class)
            ->getRepository(Website::class);
        $websites = $websiteRepository->findAll();

        foreach ($websites as $website) {
            $this->consentConfigManager->updateConsentsConfigForWebsiteScope($consent, $website);
        }

        $this->consentConfigManager->updateConsentsConfigForGlobalScope($consent);
    }
}
