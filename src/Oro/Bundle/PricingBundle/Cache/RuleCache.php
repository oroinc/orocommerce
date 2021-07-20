<?php

namespace Oro\Bundle\PricingBundle\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * Keeps cache with built pricing rule query
 */
class RuleCache implements Cache
{
    const DQL_PARTS_KEY = 'dql_parts';
    const PARAMETERS_KEY = 'parameters';
    const HASH = 'hash';

    /**
     * @var Cache
     */
    protected $cacheStorage;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var SymmetricCrypterInterface
     */
    private $crypter;

    public function __construct(Cache $cache, ManagerRegistry $registry, SymmetricCrypterInterface $crypter)
    {
        $this->cacheStorage = $cache;
        $this->registry = $registry;
        $this->crypter = $crypter;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        $data = $this->cacheStorage->fetch($id);
        if (false !== $data
            && (!empty($data[self::HASH]) && $this->getHash($data[self::DQL_PARTS_KEY]) === $data[self::HASH])
        ) {
            return $this->restoreQueryBuilder($data);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        return $this->cacheStorage->contains($id);
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0)
    {
        if ($data instanceof QueryBuilder) {
            return $this->cacheStorage->save(
                $id,
                [
                    self::DQL_PARTS_KEY => $data->getDQLParts(),
                    self::PARAMETERS_KEY => $data->getParameters(),
                    self::HASH => $this->getHash($data->getDQLParts())
                ],
                $lifeTime
            );
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        return $this->cacheStorage->delete($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getStats()
    {
        return $this->cacheStorage->getStats();
    }

    /**
     * @param array $data
     * @return QueryBuilder|bool
     */
    protected function restoreQueryBuilder(array $data)
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

    /**
     * @param array $dqlParts
     * @return string
     */
    private function getHash(array $dqlParts)
    {
        return md5($this->crypter->encryptData(serialize($dqlParts)));
    }
}
