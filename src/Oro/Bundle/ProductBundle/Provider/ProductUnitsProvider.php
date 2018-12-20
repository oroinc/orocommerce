<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;

/**
 * Provides product units
 * gets from ProductBundle\Entity\ProductUnit repository
 * apply unitLabel formatting
 */
class ProductUnitsProvider
{
    private const CACHE_KEY_CODES = 'codes';
    private const CACHE_KEY_CODES_WITH_PRECISION = 'codes_with_precision';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var UnitLabelFormatterInterface */
    protected $formatter;

    /** @var CacheProvider */
    protected $cache;

    /**
     * @param ManagerRegistry $registry
     * @param UnitLabelFormatterInterface $formatter
     * @param CacheProvider $cache
     */
    public function __construct(ManagerRegistry $registry, UnitLabelFormatterInterface $formatter, CacheProvider $cache)
    {
        $this->registry = $registry;
        $this->formatter = $formatter;
        $this->cache = $cache;
    }
    
    /**
     * @return array
     */
    public function getAvailableProductUnits(): array
    {
        $productUnitCodes = $this->cache->fetch(self::CACHE_KEY_CODES);
        if (false === $productUnitCodes) {
            $productUnitCodes = $this->getRepository()->getAllUnitCodes();

            $this->cache->save(self::CACHE_KEY_CODES, $productUnitCodes);
        }

        $unitsFull = [];
        foreach ($productUnitCodes as $code) {
            $unitsFull[$this->formatter->format($code)] = $code;
        }

        return $unitsFull;
    }

    /**
     * @return array
     */
    public function getAvailableProductUnitsWithPrecision(): array
    {
        $unitsWithPrecision = $this->cache->fetch(self::CACHE_KEY_CODES_WITH_PRECISION);
        if (false === $unitsWithPrecision) {
            $productUnits = $this->getRepository()->getAllUnits();

            $unitsWithPrecision = [];
            foreach ($productUnits as $unit) {
                $unitsWithPrecision[$unit->getCode()] = $unit->getDefaultPrecision();
            }

            $this->cache->save(self::CACHE_KEY_CODES_WITH_PRECISION, $unitsWithPrecision);
        }

        return $unitsWithPrecision;
    }

    public function clearCache(): void
    {
        $this->cache->delete(self::CACHE_KEY_CODES);
        $this->cache->delete(self::CACHE_KEY_CODES_WITH_PRECISION);
    }

    /**
     * @return ProductUnitRepository
     */
    protected function getRepository()
    {
        return $this->registry
            ->getManagerForClass(ProductUnit::class)
            ->getRepository(ProductUnit::class);
    }
}
