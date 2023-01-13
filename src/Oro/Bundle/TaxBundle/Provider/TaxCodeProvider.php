<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Entity\Repository\AbstractTaxCodeRepository;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Provider for retrieving tax code by type and object, supports memory caching
 */
class TaxCodeProvider
{
    private AbstractTaxCodeRepository $productTaxCodeRepository;
    private AbstractTaxCodeRepository $customerTaxCodeRepository;
    private CacheInterface $taxCodesCache;
    private DoctrineHelper $doctrineHelper;

    public function __construct(
        AbstractTaxCodeRepository $productTaxCodeRepository,
        AbstractTaxCodeRepository $customerTaxCodeRepository,
        CacheInterface $cacheProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->productTaxCodeRepository = $productTaxCodeRepository;
        $this->customerTaxCodeRepository = $customerTaxCodeRepository;
        $this->taxCodesCache = $cacheProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    public function getTaxCode(string $type, object $object): ?TaxCodeInterface
    {
        return $this->taxCodesCache->get($this->getCacheKey($object), function () use ($type, $object) {
            return $this->fetchSingleTaxCode($type, $object);
        });
    }

    public function preloadTaxCodes(string $type, array $objects): void
    {
        $taxCodes = $this->fetchMultipleTaxCodes($type, $objects);

        foreach ($objects as $index => $object) {
            $cacheKey = $this->getCacheKey($object);
            $this->taxCodesCache->delete($cacheKey);
            $this->taxCodesCache->get($cacheKey, function () use ($index, $taxCodes) {
                return $taxCodes[$index];
            });
        }
    }

    private function fetchSingleTaxCode(string $type, object $object): ?TaxCodeInterface
    {
        return $this->getRepository($type)->findOneByEntity($object);
    }

    private function fetchMultipleTaxCodes(string $type, array $objects): array
    {
        return $this->getRepository($type)->findManyByEntities($objects);
    }

    private function getRepository(string $type): ?AbstractTaxCodeRepository
    {
        if ($type === TaxCodeInterface::TYPE_PRODUCT) {
            return $this->productTaxCodeRepository;
        } elseif ($type === TaxCodeInterface::TYPE_ACCOUNT || $type === TaxCodeInterface::TYPE_ACCOUNT_GROUP) {
            return $this->customerTaxCodeRepository;
        }

        throw new \InvalidArgumentException(sprintf('Unknown type: %s', $type));
    }

    private function getCacheKey(object $object): string
    {
        $objectClass = ClassUtils::getClass($object);
        $ids = implode('_', $this->doctrineHelper->getEntityIdentifier($object));

        return UniversalCacheKeyGenerator::normalizeCacheKey($objectClass . '_' . $ids);
    }
}
