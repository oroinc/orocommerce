<?php

namespace Oro\Bundle\TaxBundle\Entity\Repository;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;

use Oro\Bundle\TaxBundle\Model\TaxCode;
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
     * @var CacheProvider
     */
    private $queryResultCache;

    /**
     * @return CacheProvider
     */
    private function getQueryResultCache()
    {
        if (!$this->queryResultCache) {
            $this->queryResultCache = new ArrayCache();
        }

        return $this->queryResultCache;
    }

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
     * @param array $ids
     * @return QueryBuilder
     */
    protected function getFindManyByEntitiesQueryBuilder($type, array $ids)
    {
        $type = (string)$type;

        $alias = sprintf('%s%s', $type, self::ALIAS_SUFFIX);
        $field = $this->getInflector()->camelize($this->getInflector()->pluralize($type));

        $queryBuilder = $this->createQueryBuilder($alias);

        return $queryBuilder
            ->select($alias, $field)
            ->join(sprintf('%s.%s', $alias, $field), $field)
            ->where($queryBuilder->expr()->in(sprintf('%s.id', $field), ':ids'))
            ->setParameter('ids', $ids);
    }

    /**
     * @param string $type
     * @param object $object
     *
     * @return AbstractTaxCode|null
     */
    public function findOneByEntity($type, $object)
    {
        $ids = $this->getObjectIds($object);

        if (!$ids) {
            return null;
        }

        return $this->getFindOneByEntityQuery($type, reset($ids))
            ->setResultCacheDriver($this->getQueryResultCache())
            ->useResultCache(true)
            ->getOneOrNullResult();
    }

    /**
     * @param string $type
     * @param array $objects
     * @return array|AbstractTaxCode[]
     */
    public function findManyByEntities($type, array $objects)
    {
        $notEmptyIds = [];
        $objectIds = [];
        foreach ($objects as $object) {
            $compositeId = $this->getObjectIds($object);

            if (!$compositeId) {
                $objectId = null;
            } else {
                $objectId = reset($compositeId);
                $notEmptyIds[] = $objectId;
            }

            $objectIds[] = $objectId;
        }

        $taxCodes = $this->getFindManyByEntitiesQueryBuilder($type, $notEmptyIds)
            ->getQuery()
            ->setResultCacheDriver($this->getQueryResultCache())
            ->useResultCache(true)
            ->getResult();

        return $this->arrangeTaxCodes($objectIds, $taxCodes, $type);
    }

    /**
     * @param array $objectIds
     * @param array|AbstractTaxCode[] $taxCodes
     * @param string $type
     * @return array
     */
    private function arrangeTaxCodes(array $objectIds, array $taxCodes, $type)
    {
        $results = array_fill(0, count($objectIds), null);
        $index = 0;
        foreach ($objectIds as $objectId) {
            $results[$index++] = $this->findTaxCodeByObjectId($taxCodes, $type, $objectId);
        }

        return $results;
    }

    /**
     * @param array $taxCodes
     * @param $type
     * @param $objectId
     * @return TaxCode
     */
    private function findTaxCodeByObjectId(array $taxCodes, $type, $objectId)
    {
        foreach ($taxCodes as $taxCode) {
            $collectionName = $this->getInflector()->pluralize($type);
            $collection = $this->getPropertyAccessor()->getValue($taxCode, $collectionName);

            foreach ($collection as $item) {
                $itemIds = $this->getObjectIds($item);

                if (reset($itemIds) === $objectId) {
                    return $taxCode;
                }
            }
        }

        return null;
    }

    /**
     * @param object $object
     * @return array
     */
    private function getObjectIds($object)
    {
        return $this->getEntityManager()
            ->getClassMetadata(ClassUtils::getClass($object))
            ->getIdentifierValues($object);
    }
}
