<?php

namespace Oro\Bundle\TaxBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;

/**
 * Repository for Tax Code entities
 */
abstract class AbstractTaxCodeRepository extends ServiceEntityRepository
{
    const ALIAS_SUFFIX = 'TaxCode';

    public function findOneByEntity(object $object): ?TaxCodeInterface
    {
        return $object->getTaxCode();
    }

    /**
     * @param array $objects
     * @return array|TaxCodeInterface[]
     */
    public function findManyByEntities(array $objects): array
    {
        $result = [];
        foreach ($objects as $object) {
            $result[] = $object->getTaxCode();
        }

        return $result;
    }
}
