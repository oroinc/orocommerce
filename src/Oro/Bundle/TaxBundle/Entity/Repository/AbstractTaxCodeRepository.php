<?php

namespace Oro\Bundle\TaxBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;

/**
 * Repository for Tax Code entities
 */
abstract class AbstractTaxCodeRepository extends EntityRepository
{
    const ALIAS_SUFFIX = 'TaxCode';

    /**
     * @param array $codes
     * @param Organization|null $organization
     * @return AbstractTaxCode[]
     */
    public function findByCodes(array $codes = [], Organization $organization = null)
    {
        $qb = $this->createQueryBuilder('taxCode');

        $qb
            ->where($qb->expr()->in('taxCode.code', ':codes'))
            ->setParameter('codes', $codes);

        if ($organization) {
            $qb
                ->andWhere($qb->expr()->eq('taxCode.organization', ':organization'))
                ->setParameter('organization', $organization);
        }

        return $qb
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $type
     * @param object $object
     *
     * @return null|AbstractTaxCode
     */
    public function findOneByEntity($type, $object)
    {
        return $object->getTaxCode();
    }

    /**
     * @param string $type
     * @param array $objects
     * @return array|AbstractTaxCode[]
     */
    public function findManyByEntities($type, array $objects)
    {
        $result = [];
        foreach ($objects as $object) {
            $result[] = $object->getTaxCode();
        }

        return $result;
    }
}
