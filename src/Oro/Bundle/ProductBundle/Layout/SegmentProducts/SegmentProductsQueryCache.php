<?php

namespace Oro\Bundle\ProductBundle\Layout\SegmentProducts;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

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
    private CacheProvider $cache;
    private int $cacheLifeTime;

    public function __construct(
        ManagerRegistry $doctrine,
        SymmetricCrypterInterface $crypter,
        CacheProvider $cache,
        int $cacheLifeTime
    ) {
        $this->doctrine = $doctrine;
        $this->crypter = $crypter;
        $this->cache = $cache;
        $this->cacheLifeTime = $cacheLifeTime;
    }

    public function getQuery(string $cacheKey): ?Query
    {
        $data = $this->cache->fetch($cacheKey);
        if (false === $data) {
            return null;
        }

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
        $this->cache->save($cacheKey, $data, $this->cacheLifeTime);
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
