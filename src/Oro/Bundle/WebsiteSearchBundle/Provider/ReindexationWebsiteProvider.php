<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Provides IDs of websites for which website search index should be re-indexed.
 */
class ReindexationWebsiteProvider implements ReindexationWebsiteProviderInterface
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritDoc}
     */
    public function getReindexationWebsiteIds(Website $website): array
    {
        return [$website->getId()];
    }

    /**
     * {@inheritDoc}
     */
    public function getReindexationWebsiteIdsForOrganization(Organization $organization): array
    {
        return $this->doctrine->getRepository(Website::class)->getAllWebsitesIds($organization);
    }
}
