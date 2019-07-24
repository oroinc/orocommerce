<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RedirectBundle\Entity\Slug;

/**
 * Add organization to page slug redirect
 */
class CmsPageSlugRedirectOrganization extends AbstractFixture
{
    const BATCH_SIZE = 1000;

    public function load(ObjectManager $manager): void
    {
        $this->addOrganizationToPageSlugRedirect($manager);
    }

    /**
     * @param ObjectManager|EntityManager $manager
     */
    private function addOrganizationToPageSlugRedirect(ObjectManager $manager): void
    {
        $organizations = $manager->getRepository(Organization::class)->findAll();

        /** @var Organization $organization */
        foreach ($organizations as $organization) {
            $iterator = $this->getPageSlugIdsIterator($manager, $organization);
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

    /**
     * @param ObjectManager $manager
     * @param Organization $organization
     * @return BufferedQueryResultIterator
     */
    private function getPageSlugIdsIterator(
        ObjectManager $manager,
        Organization $organization
    ): BufferedQueryResultIterator {
        /** @var EntityRepository $repository */
        $repository = $manager->getRepository(Page::class);
        $qb = $repository->createQueryBuilder('page');
        $query = $qb->select('page_slug.id')
            ->innerJoin('page.slugs', 'page_slug')
            ->where($qb->expr()->andX(
                $qb->expr()->eq('page.organization', ':organization'),
                $qb->expr()->isNull('page_slug.organization')
            ))
            ->setParameter('organization', $organization)
            ->groupBy('page_slug.id')
            ->getQuery();

        $iterator = new BufferedQueryResultIterator($query);
        $iterator->setBufferSize(self::BATCH_SIZE);

        return $iterator;
    }
}
