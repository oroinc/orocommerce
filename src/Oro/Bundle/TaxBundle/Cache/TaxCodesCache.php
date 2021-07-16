<?php

namespace Oro\Bundle\TaxBundle\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;

class TaxCodesCache
{
    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    public function __construct(CacheProvider $cacheProvider, DoctrineHelper $doctrineHelper)
    {
        $this->cacheProvider = $cacheProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param object $object
     * @return bool
     */
    public function containsTaxCode($object)
    {
        $cacheKey = $this->getCacheKey($object);

        return $this->cacheProvider->contains($cacheKey);
    }

    /**
     * @param object $object
     * @return TaxCodeInterface
     */
    public function fetchTaxCode($object)
    {
        $cacheKey = $this->getCacheKey($object);

        return $this->cacheProvider->fetch($cacheKey);
    }

    /**
     * @param object $object
     * @param TaxCodeInterface $taxCode
     * @return bool
     */
    public function saveTaxCode($object, TaxCodeInterface $taxCode = null)
    {
        $cacheKey = $this->getCacheKey($object);

        return $this->cacheProvider->save($cacheKey, $taxCode);
    }

    /**
     * @param object $object
     * @return string
     */
    private function getCacheKey($object)
    {
        $objectClass = ClassUtils::getClass($object);
        $ids = implode('_', $this->doctrineHelper->getEntityIdentifier($object));

        return $objectClass . '_' . $ids;
    }
}
