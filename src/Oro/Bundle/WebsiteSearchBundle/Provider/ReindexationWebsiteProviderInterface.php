<?php

namespace Oro\Bundle\WebsiteSearchBundle\Provider;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Represents as service that provides IDs of websites for which website search index should be re-indexed.
 */
interface ReindexationWebsiteProviderInterface
{
    /**
     * Gets IDs of websites for which website search index should be re-indexed
     * when data for the given website are changed.
     *
     * @param Website $website
     *
     * @return int[]
     */
    public function getReindexationWebsiteIds(Website $website): array;

    /**
     * Gets IDs of websites for which website search index should be re-indexed
     * when data for the given organization are changed.
     *
     * @param Organization $organization
     *
     * @return int[]
     */
    public function getReindexationWebsiteIdsForOrganization(Organization $organization): array;
}
