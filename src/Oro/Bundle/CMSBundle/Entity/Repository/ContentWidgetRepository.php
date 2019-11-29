<?php

namespace Oro\Bundle\CMSBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Doctrine repository for ContentWidget entity
 */
class ContentWidgetRepository extends EntityRepository
{
    /**
     * @param string[] $names
     * @param AclHelper $aclHelper
     * @return ContentWidget[]
     */
    public function findAllByNames(array $names, AclHelper $aclHelper): array
    {
        $qb = $this->createQueryBuilder('content_widget');

        $qb->where($qb->expr()->in('content_widget.name', ':names'))
            ->setParameter(':names', $names);

        return $aclHelper->apply($qb)->getResult();
    }

    /**
     * @param string $name
     * @param Organization $organization
     *
     * @return null|ContentWidget
     */
    public function findOneByName(string $name, Organization $organization): ?ContentWidget
    {
        $qb = $this->createQueryBuilder('content_widget');

        return $qb
            ->andWhere($qb->expr()->eq('content_widget.name', ':name'))
            ->setParameter('name', $name)
            ->andWhere($qb->expr()->eq('content_widget.organization', ':organization'))
            ->setParameter('organization', $organization->getId())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
