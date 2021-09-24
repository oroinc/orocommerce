<?php

namespace Oro\Bundle\TaxBundle\Entity\Repository;

use Doctrine\Inflector\Inflector;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Component\DoctrineUtils\Inflector\InflectorFactory;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Repository for Tax Code entities
 */
abstract class AbstractTaxCodeRepository extends EntityRepository
{
    const ALIAS_SUFFIX = 'TaxCode';

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

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

    protected function getInflector(): Inflector
    {
        return InflectorFactory::create();
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
