<?php

namespace OroB2B\Bundle\TaxBundle\Entity\Repository;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\TaxBundle\Entity\AbstractTaxCode;

abstract class AbstractTaxCodeRepository extends EntityRepository
{
    /**
     * @var Inflector
     */
    private $inflector;

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

        $alias = sprintf('%sTaxCode', $type);
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
     * @return AbstractTaxCode|null
     */
    public function findOneByEntity($type, $object)
    {
        $ids = $this->getEntityManager()->getClassMetadata(ClassUtils::getClass($object))->getIdentifierValues($object);

        if (!$ids) {
            return null;
        }

        return $this->getFindOneByEntityQuery($type, reset($ids))
            ->setResultCacheDriver($this->getQueryResultCache())
            ->useResultCache(true)
            ->getOneOrNullResult();
    }
}
