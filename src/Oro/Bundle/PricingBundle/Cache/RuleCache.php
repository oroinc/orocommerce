<?php

namespace Oro\Bundle\PricingBundle\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class RuleCache implements Cache
{
    const DQL_PARTS_KEY = 'dql_parts';
    const PARAMETERS_KEY = 'parameters';

    /**
     * @var Cache
     */
    protected $cacheStorage;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param Cache $cache
     * @param ManagerRegistry $registry
     */
    public function __construct(Cache $cache, ManagerRegistry $registry)
    {
        $this->cacheStorage = $cache;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        if ($this->contains($id)) {
            $data = $this->cacheStorage->fetch($id);
            if ($data) {
                return $this->restoreQueryBuilder($data);
            }
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
                    self::PARAMETERS_KEY => $data->getParameters()
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
}
