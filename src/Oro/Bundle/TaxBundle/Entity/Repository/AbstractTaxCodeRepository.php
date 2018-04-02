<?php

namespace Oro\Bundle\TaxBundle\Entity\Repository;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

abstract class AbstractTaxCodeRepository extends EntityRepository
{
    const ALIAS_SUFFIX = 'TaxCode';

    /**
     * @var Inflector
     */
    private $inflector;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @param array $codes
     * @return AbstractTaxCode[]
     */
    public function findByCodes(array $codes = [])
    {
        $qb = $this->createQueryBuilder('taxCode');

        return $qb
            ->where($qb->expr()->in('taxCode.code', ':codes'))
            ->setParameter('codes', $codes)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return PropertyAccessor
     * @throws \InvalidArgumentException
     */
    public function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * @return Inflector
     */
    protected function getInflector()
    {
        if (!$this->inflector) {
            $this->inflector = new Inflector();
        }

        return $this->inflector;
    }

    /**
     * @param string $type
     * @param integer $id
     * @return \Doctrine\ORM\Query
     */
    protected function getFindOneByEntityQuery($type, $id)
    {
        $type = (string)$type;
        QueryBuilderUtil::checkIdentifier($type);

        $alias = sprintf('%s%s', $type, self::ALIAS_SUFFIX);
        $field = $this->getInflector()->camelize($this->getInflector()->pluralize($type));

        $queryBuilder = $this->createQueryBuilder($alias);

        return $queryBuilder
            ->where($queryBuilder->expr()->isMemberOf(sprintf(':%s', $type), sprintf('%s.%s', $alias, $field)))
            ->setParameter($type, $id)
            ->setMaxResults(1)
            ->getQuery();
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
