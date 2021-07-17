<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\TaxBundle\Cache\TaxCodesCache;
use Oro\Bundle\TaxBundle\Entity\Repository\AbstractTaxCodeRepository;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;

class TaxCodeProvider
{
    /**
     * @var string
     */
    private $productTaxCodeRepository;

    /**
     * @var string
     */
    private $customerTaxCodeRepository;

    /**
     * @var TaxCodesCache
     */
    private $taxCodesCache;

    public function __construct(
        AbstractTaxCodeRepository $productTaxCodeRepository,
        AbstractTaxCodeRepository $customerTaxCodeRepository,
        TaxCodesCache $cacheProvider
    ) {
        $this->productTaxCodeRepository = $productTaxCodeRepository;
        $this->customerTaxCodeRepository = $customerTaxCodeRepository;
        $this->taxCodesCache = $cacheProvider;
    }

    /**
     * @param string $type
     * @param object $object
     * @return TaxCodeInterface|null
     */
    public function getTaxCode($type, $object)
    {
        if (!$this->taxCodesCache->containsTaxCode($object)) {
            $taxCode = $this->fetchSingleTaxCode($type, $object);
            $this->taxCodesCache->saveTaxCode($object, $taxCode);
        }

        return $this->taxCodesCache->fetchTaxCode($object);
    }

    /**
     * @param string $type
     * @param array $objects
     */
    public function preloadTaxCodes($type, array $objects)
    {
        $taxCodes = $this->fetchMultipleTaxCodes($type, $objects);

        $index = 0;
        foreach ($objects as $object) {
            $this->taxCodesCache->saveTaxCode($object, $taxCodes[$index++]);
        }
    }

    /**
     * @param string $type
     * @param object $object
     * @return TaxCodeInterface|null
     */
    private function fetchSingleTaxCode($type, $object)
    {
        return $this->getRepository($type)->findOneByEntity((string)$type, $object);
    }

    /**
     * @param string $type
     * @param array $objects
     * @return array|TaxCodeInterface[]
     */
    private function fetchMultipleTaxCodes($type, array $objects)
    {
        return $this->getRepository($type)->findManyByEntities((string)$type, $objects);
    }

    /**
     * @param string $type
     * @return AbstractTaxCodeRepository
     * @throws \InvalidArgumentException
     */
    private function getRepository($type)
    {
        if ($type === TaxCodeInterface::TYPE_PRODUCT) {
            return $this->productTaxCodeRepository;
        } elseif ($type === TaxCodeInterface::TYPE_ACCOUNT || $type === TaxCodeInterface::TYPE_ACCOUNT_GROUP) {
            return $this->customerTaxCodeRepository;
        }

        throw new \InvalidArgumentException(sprintf('Unknown type: %s', $type));
    }
}
