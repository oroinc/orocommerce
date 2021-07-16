<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Responsible for saving, processing and return the results of segment from the cache and
 * checking the integrity of the data coming.
 * Ensures that the cached data is not used in case of substitution by a third party
 */
class SegmentProductsProvider
{
    private const DQL = 'dql';
    private const PARAMETERS = 'parameters';
    private const HASH = 'hash';
    private const HINTS = 'hints';

    /** @var SegmentManager */
    private $segmentManager;

    /** @var ProductManager */
    private $productManager;

    /** @var ManagerRegistry */
    private $registry;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var SymmetricCrypterInterface */
    private $crypter;

    /** @var AclHelper */
    private $aclHelper;

    /** @var CacheProvider */
    private $cache;

    /** @var int */
    private $cacheLifeTime;

    public function __construct(
        SegmentManager $segmentManager,
        ProductManager $productManager,
        ManagerRegistry $registry,
        TokenStorageInterface $tokenStorage,
        SymmetricCrypterInterface $crypter,
        AclHelper $aclHelper
    ) {
        $this->segmentManager = $segmentManager;
        $this->productManager = $productManager;
        $this->registry = $registry;
        $this->tokenStorage = $tokenStorage;
        $this->crypter = $crypter;
        $this->aclHelper = $aclHelper;
    }

    public function setCache(CacheProvider $cache, int $lifeTime = 0): void
    {
        $this->cache = $cache;
        $this->cacheLifeTime = $lifeTime;
    }

    /**
     * @param Segment $segment
     * @param int $minimumItemsLimit
     * @param int $maximumItemsLimit
     * @return array|Product[]
     */
    public function getProducts(Segment $segment, int $minimumItemsLimit, int $maximumItemsLimit): array
    {
        if ($maximumItemsLimit <= 0 || $minimumItemsLimit > $maximumItemsLimit) {
            return [];
        }

        $cacheKey = implode('_', $this->getCacheParts($segment));
        $data = $this->cache->fetch($cacheKey);
        if (is_array($data) && $this->checkCacheDataConsistency($data)) {
            return $this->getResult($data, $minimumItemsLimit, $maximumItemsLimit);
        }

        $qb = $this->segmentManager->getEntityQueryBuilder($segment);
        if ($qb) {
            $qb = $this->productManager->restrictQueryBuilder($qb, []);

            $data = $this->getCachedData(
                $qb->getDQL(),
                $qb->getParameters()->toArray(),
                $qb->getEntityManager()->getConfiguration()->getDefaultQueryHints()
            );
            $this->cache->save($cacheKey, $data, $this->cacheLifeTime);

            return $this->getResult($data, $minimumItemsLimit, $maximumItemsLimit);
        }

        return [];
    }

    private function getCacheParts(Segment $segment): array
    {
        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;

        return [
            'segment_products',
            $user instanceof AbstractUser ? (int) $user->getId() : 0,
            $segment->getId(),
            $segment->getRecordsLimit()
        ];
    }

    /**
     * @param array $data
     * @param int $minimumItemsLimit
     * @param int $maximumItemsLimit
     * @return array|Product[]
     */
    private function getResult(array $data, int $minimumItemsLimit, int $maximumItemsLimit): array
    {
        $em = $this->registry->getManagerForClass(Product::class);

        if (!empty($data[self::HINTS])) {
            $configuration = $em->getConfiguration();
            foreach ($data[self::HINTS] as $name => $value) {
                $configuration->setDefaultQueryHint($name, $value);
            }
        }

        /** @var Query $query */
        $query = $em->createQuery($data[self::DQL]);
        $query = $this->aclHelper->apply($query);

        $products = $query->setMaxResults($maximumItemsLimit)
            ->execute($data[self::PARAMETERS]);

        if (count($products) < $minimumItemsLimit) {
            return [];
        }

        return $products;
    }

    /**
     * @param string $dql
     * @param Parameter[] $parameters
     * @param array $queryHints
     * @return array
     */
    private function getCachedData($dql, array $parameters, array $queryHints): array
    {
        /** @var Parameter $parameter */
        $resultParameters = [];
        foreach ($parameters as $parameter) {
            $resultParameters[$parameter->getName()] = $parameter->getValue();
        }

        return [
            self::DQL => $dql,
            self::PARAMETERS => $resultParameters,
            self::HINTS => $queryHints,
            self::HASH => $this->getEncryptedData($dql, $resultParameters, $queryHints),
        ];
    }

    private function checkCacheDataConsistency(array $data): bool
    {
        if (!empty($data[self::DQL]) &&
            !empty($data[self::HASH]) &&
            !empty($data[self::PARAMETERS])
        ) {
            $hash = $this->crypter->decryptData($data[self::HASH]);

            return $hash === $this->getHashData(
                $data[self::DQL],
                $data[self::PARAMETERS],
                $data[self::HINTS] ?? []
            );
        }

        return false;
    }

    private function getEncryptedData(string $dql, array $parameters = [], array $queryHints = []): ?string
    {
        $data = $this->getHashData($dql, $parameters, $queryHints);

        return $this->crypter->encryptData($data);
    }

    private function getHashData(string $dql, array $parameters = [], array $queryHints = []): string
    {
        return md5(serialize([
            self::DQL => $dql,
            self::PARAMETERS => $parameters,
            self::HINTS => $queryHints,
        ]));
    }
}
