<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Resolves all website IDs for a product attribute reindex.
 */
class ReindexProductsByAttributesWebsiteResolver implements ReindexProductsByAttributesWebsiteResolverInterface
{
    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getWebsiteIdsToReindex(array $attributeIds): array
    {
        /** @var WebsiteRepository $repository */
        $repository = $this->doctrine->getRepository(Website::class);

        return $repository->getAllWebsitesIds();
    }
}
