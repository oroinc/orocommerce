<?php

namespace Oro\Bundle\ConsentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Doctrine repository for Consent entity
 */
class ConsentRepository extends EntityRepository
{
    /**
     * @param array $consentIds
     *
     * @return array
     */
    public function getNonExistentConsentIds(array $consentIds)
    {
        if (empty($consentIds)) {
            return [];
        }

        $consentIds = array_unique($consentIds);

        $qb = $this->createQueryBuilder('c');
        $qb
            ->select('c.id')
            ->where($qb->expr()->in('c.id', ':consentIds'));

        $qb->setParameter('consentIds', $consentIds);

        $result = $qb->getQuery()->getArrayResult();

        $existingConsentIds = array_column($result, 'id');

        return array_diff($consentIds, $existingConsentIds);
    }
}
