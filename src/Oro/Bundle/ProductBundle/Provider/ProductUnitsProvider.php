<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Provides product units
 * gets from ProductBundle\Entity\ProductUnit repository
 * apply unitLabel formatting
 */
class ProductUnitsProvider
{
    private const CACHE_KEY_CODES = 'codes';
    private const CACHE_KEY_CODES_WITH_PRECISION = 'codes_with_precision';

    protected ManagerRegistry $registry;
    protected UnitLabelFormatterInterface $formatter;
    protected CacheInterface $cache;

    public function __construct(
        ManagerRegistry $registry,
        UnitLabelFormatterInterface $formatter,
        CacheInterface $cache
    ) {
        $this->registry = $registry;
        $this->formatter = $formatter;
        $this->cache = $cache;
    }

    public function getAvailableProductUnits(): array
    {
        $productUnitCodes = $this->cache->get(self::CACHE_KEY_CODES, function () {
            return $this->getRepository()->getAllUnitCodes();
        });

        $unitsFull = [];
        foreach ($productUnitCodes as $code) {
            $unitsFull[$this->formatter->format($code)] = $code;
        }

        return $unitsFull;
    }

    public function getAvailableProductUnitsWithPrecision(): array
    {
        return $this->cache->get(self::CACHE_KEY_CODES_WITH_PRECISION, function () {
            $productUnits = $this->getRepository()->getAllUnits();
            $unitsWithPrecision = [];
            foreach ($productUnits as $unit) {
                $unitsWithPrecision[$unit->getCode()] = $unit->getDefaultPrecision();
            }
            return $unitsWithPrecision;
        });
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
