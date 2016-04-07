<?php

namespace OroB2B\Bundle\TaxBundle\Entity\Repository;

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
     * @param object $object
     *
     * @return AbstractTaxCode|null
     */
    public function findOneByEntity($type, $object)
    {
        $type = (string)$type;
        $ids = $this->getEntityManager()->getClassMetadata(ClassUtils::getClass($object))->getIdentifierValues($object);

        if (!$ids) {
            return null;
        }

        $alias = sprintf('%sTaxCode', $type);
        $field = $this->getInflector()->camelize($this->getInflector()->pluralize($type));

        return $this->createQueryBuilder($alias)
            ->where(sprintf(':%s MEMBER OF %s.%s', $type, $alias, $field))
            ->setParameter($type, reset($ids))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
