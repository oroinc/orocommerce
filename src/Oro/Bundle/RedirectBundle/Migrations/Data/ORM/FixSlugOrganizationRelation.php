<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\DoctrineUtils\ORM\ResultSetMappingUtil;
use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Set Slug organization same to owning entity organization if slug organization is empty.
 */
class FixSlugOrganizationRelation extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @param EntityManagerInterface $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getBrokenSlugs($manager) as $slug) {
            $entitiesWithoutOrgInSlug = $this->fixSlugsWithoutOrgWithSameUrl($manager, $slug);

            $entity = $this->getEntityBySlug($slug);
            if (!$entity) {
                continue;
            }
            $this->updateEntitySlugs($entity);

            array_shift($entitiesWithoutOrgInSlug);
            foreach ($entitiesWithoutOrgInSlug as $entity) {
                $this->updateEntitySlugs($entity);
            }

            $manager->flush();
        }

        $this->fillLostSlugOrganization($manager);
    }

    private function fillLostSlugOrganization(EntityManagerInterface $manager): void
    {
        foreach ($manager->getMetadataFactory()->getAllMetadata() as $metadata) {
            if (is_a($metadata->getName(), SlugAwareInterface::class, true)
                && is_a($metadata->getName(), OrganizationAwareInterface::class, true)
            ) {
                $this->updateSlugOrganization($manager, $metadata);
            }
        }
    }

    /**
     * @param EntityManagerInterface $manager
     * @param Slug $slug
     * @return array|Slug[]
     */
    private function getSlugsWithoutOrganization(EntityManagerInterface $manager, Slug $slug)
    {
        $qb = $manager->createQueryBuilder();
        $qb->select('slug')
            ->from(Slug::class, 'slug')
            ->where($qb->expr()->andX(
                $qb->expr()->isNull('slug.organization'),
                $qb->expr()->eq('slug.urlHash', ':urlHash')
            ))
            ->setParameter('urlHash', md5($slug->getUrl()));

        return $qb->getQuery()->getResult();
    }

    /**
     * @param EntityManagerInterface $manager
     * @return BufferedIdentityQueryResultIterator|Slug[]
     */
    private function getBrokenSlugs(EntityManagerInterface $manager)
    {
        $qb1 = $manager->createQueryBuilder();
        $qb1->select('sb.id')
            ->from(Slug::class, 'sb')
            ->where(
                $qb1->expr()->andX(
                    $qb1->expr()->isNull('sb.organization'),
                    $qb1->expr()->eq('sb.urlHash', 'slug.urlHash')
                )
            );

        $qb = $manager->createQueryBuilder();
        $qb->select('slug')
            ->from(Slug::class, 'slug')
            ->leftJoin('slug.scopes', 'scope')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull('slug.organization'),
                    $qb->expr()->isNull('scope.id'),
                    $qb->expr()->exists($qb1->getDQL())
                )
            );

        return new BufferedIdentityQueryResultIterator($qb);
    }

    /**
     * @param EntityManagerInterface $manager
     * @param ClassMetadata $metadata
     * @param null|mixed $entityId
     */
    private function updateSlugOrganization(
        EntityManagerInterface $manager,
        ClassMetadata $metadata,
        $entityId = null
    ): void {
        $relationMapping = $metadata->getAssociationMapping('slugs');
        $relationTable = $relationMapping['joinTable']['name'];
        $entityRelation = $relationMapping['joinTable']['joinColumns'][0]['name'];

        $expr = $manager->getExpressionBuilder();
        $rsm = ResultSetMappingUtil::createResultSetMapping(
            $manager->getConnection()->getDatabasePlatform()
        );
        $updateQB = new SqlQueryBuilder($manager, $rsm);
        $updateQB->update('oro_redirect_slug', 'slug')
            ->innerJoin(
                'slug',
                $relationTable,
                'slug_entity',
                $expr->eq('slug_entity.slug_id', 'slug.id')
            )
            ->innerJoin(
                'slug_entity',
                $metadata->getTableName(),
                'entity',
                $expr->eq(QueryBuilderUtil::getField('slug_entity', $entityRelation), 'entity.id')
            )
            ->where(
                $expr->andX(
                    $expr->isNull('slug.organization_id'),
                    $expr->isNotNull('entity.organization_id')
                )
            );

        if ($entityId) {
            $updateQB->andWhere($updateQB->expr()->eq('entity.id', ':entityId'))
                ->setParameter('entityId', $entityId);
        }

        if ($manager->getConnection()->getDatabasePlatform() instanceof MySqlPlatform) {
            $updateQB->set('slug.organization_id', 'entity.organization_id');
        } else {
            $updateQB->set('organization_id', 'entity.organization_id');
        }

        $updateQB->execute();
    }

    private function getEntityBySlug(Slug $slug): ?SlugAwareInterface
    {
        return $this->container
            ->get('oro_redirect.provider.slug_source_entity_provider_registry')
            ->getSourceEntityBySlug($slug);
    }

    private function updateEntitySlugs(SlugAwareInterface $entity): void
    {
        if (!$entity instanceof SluggableInterface) {
            return;
        }

        $this->container->get('oro_redirect.generator.slug_entity')->generate($entity, false);
    }

    protected function fixSlugsWithoutOrgWithSameUrl($manager, $slug): array
    {
        $entitiesWithoutOrgInSlug = [];
        $slugsWithoutOrg = $this->getSlugsWithoutOrganization($manager, $slug);
        foreach ($slugsWithoutOrg as $slugWithoutOrg) {
            $entity = $this->getEntityBySlug($slugWithoutOrg);
            if (!$entity) {
                continue;
            }
            $metadata = $manager->getClassMetadata(ClassUtils::getClass($entity));
            $this->updateSlugOrganization($manager, $metadata, $entity->getId());
            $entitiesWithoutOrgInSlug[] = $entity;
        }

        return $entitiesWithoutOrgInSlug;
    }
}
