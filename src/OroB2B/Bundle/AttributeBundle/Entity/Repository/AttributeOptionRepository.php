<?php

namespace OroB2B\Bundle\AttributeBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use OroB2B\Bundle\AttributeBundle\Entity\Attribute;

class AttributeOptionRepository extends EntityRepository
{
    /**
     * @param Attribute $attribute
     * @return QueryBuilder
     */
    public function createAttributeOptionsQueryBuilder(Attribute $attribute)
    {
        return $this->createQueryBuilder('option')
            ->andWhere('option.attribute = :attribute')
            ->andWhere('option.locale IS NULL')
            ->orderBy('option.order', 'ASC')
            ->setParameter('attribute', $attribute);
    }
}
