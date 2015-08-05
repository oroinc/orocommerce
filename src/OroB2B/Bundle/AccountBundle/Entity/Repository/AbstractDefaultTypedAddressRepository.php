<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

abstract class AbstractDefaultTypedAddressRepository extends EntityRepository
{
    /**
     * @param object $owner
     * @param string $type
     * @return QueryBuilder
     */
    public function getAddressesByTypeQueryBuilder($owner, $type)
    {
        $qb = $this->createQueryBuilder('a');
        $qb
            ->innerJoin(
                'a.types',
                'types',
                Join::WITH,
                $qb->expr()->eq('IDENTITY(types.type)', ':type')
            )
            ->setParameter('type', $type)
            ->andWhere($qb->expr()->eq('a.owner', ':owner'))
            ->setParameter('owner', $owner);

        return $qb;
    }

    /**
     * @param object $owner
     * @param string|null $type
     * @return QueryBuilder
     */
    public function getDefaultAddressesQueryBuilder($owner, $type = null)
    {
        $qb = $this->createQueryBuilder('a');
        $joinConditions = $qb->expr()->andX($qb->expr()->eq('types.default', ':isDefault'));
        if ($type) {
            $joinConditions->add($qb->expr()->eq('IDENTITY(types.type)', ':type'));
            $qb->setParameter('type', $type);
        }

        $qb
            ->innerJoin('a.types', 'types', Join::ON, $joinConditions)
            ->setParameter('isDefault', true)
            ->andWhere($qb->expr()->eq('a.owner', ':owner'))
            ->setParameter('owner', $owner);

        return $qb;
    }
}
