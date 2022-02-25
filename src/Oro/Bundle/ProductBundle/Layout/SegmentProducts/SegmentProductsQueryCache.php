<?php

namespace Oro\Bundle\ProductBundle\Layout\SegmentProducts;

use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * The cache for queries that are used to retrieve segment products.
 */
class SegmentProductsQueryCache
{
    private const DQL = 'dql';
    private const PARAMETERS = 'parameters';
    private const HINTS = 'hints';
    private const HASH = 'hash';

    private ManagerRegistry $doctrine;
    private SymmetricCrypterInterface $crypter;
    private CacheItemPoolInterface $cache;
    private int $cacheLifeTime;

    public function __construct(
        ManagerRegistry $doctrine,
        SymmetricCrypterInterface $crypter,
        CacheItemPoolInterface $cache,
        int $cacheLifeTime
    ) {
        $this->doctrine = $doctrine;
        $this->crypter = $crypter;
        $this->cache = $cache;
        $this->cacheLifeTime = $cacheLifeTime;
    }

    public function getQuery(string $cacheKey): ?Query
    {
        $cacheKey = UniversalCacheKeyGenerator::normalizeCacheKey($cacheKey);
        $cacheItem = $this->cache->getItem($cacheKey);
        if (!$cacheItem->isHit()) {
            return null;
        }
        $data = $cacheItem->get();

        if (!$this->checkCacheDataConsistency($data)) {
            return null;
        }

        /** @var Query $query */
        $query = $this->doctrine->getManagerForClass(Product::class)->createQuery($data[self::DQL]);
        foreach ($data[self::PARAMETERS] as $name => $value) {
            $query->setParameter($name, $value);
        }
        if (!empty($data[self::HINTS])) {
            foreach ($data[self::HINTS] as $name => $value) {
                $query->setHint($name, $value);
            }
        }

        return $query;
    }

    public function setQuery(string $cacheKey, Query $query): void
    {
        $dql = $query->getDQL();
        $parameters = [];
        $queryBuilderParameters = $query->getParameters()->toArray();
        foreach ($queryBuilderParameters as $parameter) {
            $parameters[$parameter->getName()] = $parameter->getValue();
        }
        $hints = $query->getHints();

        $data = [
            self::DQL        => $dql,
            self::PARAMETERS => $parameters,
            self::HINTS      => $hints,
            self::HASH       => $this->crypter->encryptData($this->getHash($dql, $parameters, $hints))
        ];
        $cacheKey = UniversalCacheKeyGenerator::normalizeCacheKey($cacheKey);
        $cacheItem = $this->cache->getItem($cacheKey);
        $cacheItem->expiresAfter($this->cacheLifeTime)->set($data);
        $this->cache->save($cacheItem);
    }

    private function checkCacheDataConsistency(array $data): bool
    {
        if (empty($data[self::DQL]) || empty($data[self::PARAMETERS]) || empty($data[self::HASH])) {
            return false;
        }

        $hash = $this->crypter->decryptData($data[self::HASH]);

        return $this->getHash($data[self::DQL], $data[self::PARAMETERS], $data[self::HINTS]) === $hash;
    }

    private function getHash(string $dql, array $parameters, array $hints): string
    {
        return md5(serialize([
            self::DQL        => $dql,
            self::PARAMETERS => $parameters,
            self::HINTS      => $hints
        ]));
    }
}
