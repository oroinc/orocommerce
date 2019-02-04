<?php

namespace Oro\Bundle\ConsentBundle\EventListener;

use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfigManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * Event Listener that cleans config if consent was deleted
 */
class RemoveFromConfigurationConsentEntityListener
{
    /**
     * @var ConsentConfigManager
     */
    private $consentConfigManager;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     * @param ConsentConfigManager $consentConfigManager
     */
    public function __construct(
        ManagerRegistry $doctrine,
        ConsentConfigManager $consentConfigManager
    ) {
        $this->doctrine = $doctrine;
        $this->consentConfigManager = $consentConfigManager;
    }

    /**
     * @param Consent $consent
     */
    public function preRemove(Consent $consent)
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
