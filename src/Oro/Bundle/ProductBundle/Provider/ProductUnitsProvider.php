<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class ProductUnitsProvider
{
    private const CACHE_KEY_CODES = 'codes';
    private const CACHE_KEY_CODES_WITH_PRECISION = 'codes_with_precision';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var ProductUnitLabelFormatter */
    protected $formatter;

    /** @var CacheProvider */
    protected $cache;

    /**
     * @param ManagerRegistry $registry
     * @param ProductUnitLabelFormatter $formatter
     */
    public function __construct(ManagerRegistry $registry, ProductUnitLabelFormatter $formatter)
    {
        $this->registry = $registry;
        $this->formatter = $formatter;
    }

    /**
     * @param CacheProvider $cache
     */
    public function setCache(CacheProvider $cache): void
    {
        $this->cache = $cache;
    }

    /**
     * @return array
     */
    public function getAvailableProductUnits()
    {
        $productUnitCodes = $this->cache ? $this->cache->fetch(self::CACHE_KEY_CODES) : false;
        if (false === $productUnitCodes) {
            $productUnitCodes = $this->getRepository()->getAllUnitCodes();

            if ($this->cache) {
                $this->cache->save(self::CACHE_KEY_CODES, $productUnitCodes);
            }
        }

        $unitsFull = [];
        foreach ($productUnitCodes as $code) {
            $unitsFull[$code] = $this->formatter->format($code);
        }

        return $unitsFull;
    }

    /**
     * @return array
     */
    public function getAvailableProductUnitsWithPrecision()
    {
        $unitsWithPrecision = $this->cache ? $this->cache->fetch(self::CACHE_KEY_CODES_WITH_PRECISION) : false;
        if (false === $unitsWithPrecision) {
            $productUnits = $this->getRepository()->getAllUnits();

            $unitsWithPrecision = [];
            foreach ($productUnits as $unit) {
                $unitsWithPrecision[$unit->getCode()] = $unit->getDefaultPrecision();
            }

            if ($this->cache) {
                $this->cache->save(self::CACHE_KEY_CODES_WITH_PRECISION, $unitsWithPrecision);
            }
        }

        return $unitsWithPrecision;
    }

    public function clearCache(): void
    {
        if ($this->cache) {
            $this->cache->delete(self::CACHE_KEY_CODES);
            $this->cache->delete(self::CACHE_KEY_CODES_WITH_PRECISION);
        }
    }

    /**
     * @return ProductUnitRepository
     */
    protected function getRepository()
    {
        return $this->registry
            ->getManagerForClass('Oro\Bundle\ProductBundle\Entity\ProductUnit')
            ->getRepository('Oro\Bundle\ProductBundle\Entity\ProductUnit');
    }
}
