<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RedirectBundle\Entity\Slug;

/**
 * Update organization for all products slugs
 */
class UpdateProductSlugOrganization extends AbstractFixture
{
    const BATCH_SIZE = 1000;

    /**
     * {@inheritdoc}
     *
     * @param ObjectManager|EntityManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $organizations = $manager->getRepository(Organization::class)->findAll();
        foreach ($organizations as $organization) {
            $iterator = $this->getProductsSlugsIdsIterator($manager, $organization);
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
     * @param ObjectManager|EntityManager $manager
     * @param Organization $organization
     *
     * @return BufferedQueryResultIterator
     */
    private function getProductsSlugsIdsIterator(ObjectManager $manager, Organization $organization)
    {
        $repository = $manager->getRepository(Product::class);
        $qb = $repository->createQueryBuilder('product');

        $query = $qb->select('product_slug.id')
            ->innerJoin('product.slugs', 'product_slug')
            ->where($qb->expr()->andX(
                $qb->expr()->eq('product.organization', ':organization'),
                $qb->expr()->isNull('product_slug.organization')
            ))
            ->setParameter('organization', $organization)
            ->groupBy('product_slug.id')
            ->getQuery();

        $iterator = new BufferedQueryResultIterator($query);
        $iterator->setBufferSize(self::BATCH_SIZE);

        return $iterator;
    }
}
