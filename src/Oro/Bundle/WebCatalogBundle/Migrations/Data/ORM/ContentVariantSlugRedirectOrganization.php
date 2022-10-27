<?php

namespace Oro\Bundle\WebCatalogBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;

/**
 * Add organization to content variant slug redirect
 */
class ContentVariantSlugRedirectOrganization extends AbstractFixture
{
    const BATCH_SIZE = 1000;

    public function load(ObjectManager $manager): void
    {
        $this->addOrganizationToContentVariantSlugRedirect($manager);
    }

    /**
     * @param ObjectManager|EntityManager $manager
     */
    private function addOrganizationToContentVariantSlugRedirect(ObjectManager $manager): void
    {
        $organizations = $manager->getRepository(Organization::class)->findAll();

        /** @var Organization $organization */
        foreach ($organizations as $organization) {
            $iterator = $this->getContentVariantSlugIdsIterator($manager, $organization);
            $iterator->setPageLoadedCallback(function (array $rows) use ($manager, $organization) {
                $ids = array_column($rows, 'id');
                $qb = $manager->createQueryBuilder();
                $qb->update(Slug::class, 'slug')
                    ->set('slug.organization', ':organization')
                    ->setParameter('organization', $organization)
                    ->where($qb->expr()->in('slug.id', ':ids'))
                    ->setParameter('ids', $ids)
                    ->getQuery()
                    ->execute();

                return $rows;
            });

            foreach ($iterator as $item) {
                // iterate over all collection to trigger `pageLoadedCallback`
                continue;
            }
        }
    }

    private function getContentVariantSlugIdsIterator(
        ObjectManager $manager,
        Organization $organization
    ): BufferedQueryResultIterator {
        /** @var EntityRepository $repository */
        $repository = $manager->getRepository(ContentVariant::class);
        $qb = $repository->createQueryBuilder('content_variant');
        $query = $qb->select('content_variant_slug.id')
            ->innerJoin('content_variant.node', 'content_variant_node')
            ->innerJoin('content_variant_node.webCatalog', 'web_catalog')
            ->innerJoin('content_variant.slugs', 'content_variant_slug')
            ->where($qb->expr()->andX(
                $qb->expr()->eq('web_catalog.organization', ':organization'),
                $qb->expr()->isNull('content_variant_slug.organization')
            ))
            ->setParameter('organization', $organization)
            ->groupBy('content_variant_slug.id')
            ->getQuery();

        $iterator = new BufferedQueryResultIterator($query);
        $iterator->setBufferSize(self::BATCH_SIZE);

        return $iterator;
    }
}
