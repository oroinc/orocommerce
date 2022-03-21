<?php

namespace Oro\Bundle\PricingBundle\Cache;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Keeps cache with built pricing rule query
 */
class RuleCache
{
    private const DQL_PARTS_KEY = 'dql_parts';
    private const PARAMETERS_KEY = 'parameters';
    private const HASH = 'hash';

    private CacheItemPoolInterface $cacheStorage;
    private ManagerRegistry $registry;
    private SymmetricCrypterInterface $crypter;

    public function __construct(
        CacheItemPoolInterface $cache,
        ManagerRegistry $registry,
        SymmetricCrypterInterface $crypter
    ) {
        $this->cacheStorage = $cache;
        $this->registry = $registry;
        $this->crypter = $crypter;
    }

    public function fetch(string $id): bool|QueryBuilder
    {
        $cacheItem = $this->cacheStorage->getItem($id);
        if ($cacheItem->isHit()) {
            $data = $cacheItem->get();
            if ((!empty($data[self::HASH]) && $this->getHash($data[self::DQL_PARTS_KEY]) === $data[self::HASH])) {
                return $this->restoreQueryBuilder($data);
            }
        }

        return false;
    }

    public function contains(string $id): bool
    {
        return $this->cacheStorage->hasItem($id);
    }

    public function save(string $id, mixed $data, int $lifeTime = 0): bool
    {
        if ($data instanceof QueryBuilder) {
            $cacheItem = $this->cacheStorage->getItem($id);
            return $this->cacheStorage->save($cacheItem->set([
                self::DQL_PARTS_KEY => $data->getDQLParts(),
                self::PARAMETERS_KEY => $data->getParameters(),
                self::HASH => $this->getHash($data->getDQLParts())
            ])->expiresAfter($lifeTime));
        }

        return false;
    }

    public function delete(string $id): bool
    {
        return $this->cacheStorage->deleteItem($id);
    }

    protected function restoreQueryBuilder(array $data): QueryBuilder|bool
    {
        if (empty($data[self::DQL_PARTS_KEY]) || !array_key_exists(self::PARAMETERS_KEY, $data)) {
            return false;
        }

        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManager();
        $qb = $em->createQueryBuilder();

        $dqlParts = $data[self::DQL_PARTS_KEY];
        foreach ($dqlParts as $part => $elements) {
            if ($elements) {
                $qb->add($part, $elements);
            }
        }

        $qb->setParameters($data[self::PARAMETERS_KEY]);

        return $qb;
    }

    private function getHash(array $dqlParts): string
    {
        return md5($this->crypter->encryptData(serialize($dqlParts)));
    }
}
