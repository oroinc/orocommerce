<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\EventListener\WebsiteSearch;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\OrganizationBundle\Provider\OrganizationRestrictionProviderInterface;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository\SuggestionRepository;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Suggestion;

/**
 * Limits the search index for product suggestions by an organization and localizations in which they are located.
 */
class ProductSuggestionRestrictIndexListener implements FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;

    public function __construct(
        private WebsiteContextManager $websiteContextManager,
        private OrganizationRestrictionProviderInterface $organizationRestrictionProvider,
        private AbstractWebsiteLocalizationProvider $websiteLocalizationProvider,
        private ManagerRegistry $registry
    ) {
    }

    public function onRestrictIndexEntityEvent(RestrictIndexEntityEvent $event): void
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $qb = $event->getQueryBuilder();
        if (Suggestion::class !== $qb->getRootEntities()[0]) {
            return;
        }

        $website = $this->websiteContextManager->getWebsite($event->getContext());
        if (null === $website) {
            return;
        }

        $localizations = $this->websiteLocalizationProvider->getLocalizationsByWebsiteId($website->getId());
        if ($localizations) {
            $this->getProductSuggestionRepository()->applyLocalizationRestrictions($qb, $localizations);
        }

        $organization = $website->getOrganization();
        if ($organization) {
            $this->organizationRestrictionProvider->applyOrganizationRestrictions($qb, $organization);
        }
    }

    private function getProductSuggestionRepository(): SuggestionRepository
    {
        return $this->registry->getRepository(Suggestion::class);
    }
}
