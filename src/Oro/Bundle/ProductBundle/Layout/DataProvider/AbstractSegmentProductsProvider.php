<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Provider\ProductsProviderInterface;
use Oro\Bundle\ProductBundle\Provider\Segment\ProductSegmentProviderInterface;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Responsible for saving, processing and return the results of segment from the cache and
 * checking the integrity of the data coming.
 * Ensures that the cached data is not used in case of substitution by a third party
 */
abstract class AbstractSegmentProductsProvider implements ProductsProviderInterface
{
    const DQL = 'dql';
    const PARAMETERS = 'parameters';
    const HASH = 'hash';
    const HINTS = 'hints';

    /** @var SegmentManager */
    private $segmentManager;

    /** @var WebsiteManager */
    private $websiteManager;

    /** @var ProductSegmentProviderInterface */
    private $productSegmentProvider;

    /** @var ProductManager */
    private $productManager;

    /** @var ConfigManager */
    private $configManager;

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
        WebsiteManager $websiteManager,
        ProductSegmentProviderInterface $productSegmentProvider,
        ProductManager $productManager,
        ConfigManager $configManager,
        ManagerRegistry $registry,
        TokenStorageInterface $tokenStorage,
        SymmetricCrypterInterface $crypter,
        AclHelper $aclHelper
    ) {
        $this->segmentManager = $segmentManager;
        $this->websiteManager = $websiteManager;
        $this->productSegmentProvider = $productSegmentProvider;
        $this->productManager = $productManager;
        $this->configManager = $configManager;
        $this->registry = $registry;
        $this->tokenStorage = $tokenStorage;
        $this->crypter = $crypter;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param CacheProvider $cache
     * @param int           $lifeTime
     */
    public function setCache(CacheProvider $cache, $lifeTime = 0)
    {
        $this->cache = $cache;
        $this->cacheLifeTime = $lifeTime;
    }

    /**
     * @return int
     */
    abstract protected function getSegmentId();

    /**
     * @param Segment $segment
     *
     * @return array
     */
    abstract protected function getCacheParts(Segment $segment);

    /**
     * @param Segment $segment
     *
     * @return QueryBuilder|null
     */
    abstract protected function getQueryBuilder(Segment $segment);

    /**
     * {@inheritDoc}
     */
    public function getProducts()
    {
        if (!$this->getSegmentId()) {
            return [];
        }

        $segment = $this->productSegmentProvider->getProductSegmentById($this->getSegmentId());
        if ($segment) {
            $cacheKey = implode('_', $this->getCacheParts($segment));
            $data = $this->cache->fetch($cacheKey);
            if (is_array($data) && $this->checkCacheDataConsistency($data)) {
                return $this->getResult($data);
            }

            $qb = $this->getQueryBuilder($segment);
            if ($qb) {
                $data = $this->getCachedData(
                    $qb->getDQL(),
                    $qb->getParameters()->toArray(),
                    $qb->getEntityManager()->getConfiguration()->getDefaultQueryHints()
                );
                $this->cache->save($cacheKey, $data, $this->cacheLifeTime);

                return $this->getResult($data);
            }
        }

        return [];
    }

    /**
     * @return ManagerRegistry
     */
    public function getRegistry()
    {
        return $this->registry;
    }

    /**
     * @return ProductManager
     */
    protected function getProductManager()
    {
        return $this->productManager;
    }

    /**
     * @return SegmentManager
     */
    protected function getSegmentManager()
    {
        return $this->segmentManager;
    }

    protected function getWebsiteManager(): WebsiteManager
    {
        return $this->websiteManager;
    }

    /**
     * @return TokenStorageInterface
     */
    protected function getTokenStorage()
    {
        return $this->tokenStorage;
    }

    /**
     * @return ConfigManager
     */
    protected function getConfigManager()
    {
        return $this->configManager;
    }

    /**
     * @param array $data
     * @return \Doctrine\ORM\Query
     */
    protected function restoreQuery(array $data)
    {
        $em = $this->getRegistry()->getManager();

        if (!empty($data[self::HINTS])) {
            $configuration = $em->getConfiguration();
            foreach ($data[self::HINTS] as $name => $value) {
                $configuration->setDefaultQueryHint($name, $value);
            }
        }

        $query = $em->createQuery($data[self::DQL]);

        return $this->aclHelper->apply($query);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function getResult(array $data)
    {
        return $this->restoreQuery($data)->execute($data[self::PARAMETERS]);
    }

    /**
     * @param string $dql
     * @param Parameter[] $parameters
     * @param array $queryHints
     *
     * @return array
     */
    private function getCachedData($dql, array $parameters, array $queryHints)
    {
        /** @var Parameter $parameter */
        $resultParameters = [];
        foreach ($parameters as $parameter) {
            $resultParameters[$parameter->getName()] = $parameter->getValue();
        }

        $result = [
            self::DQL => $dql,
            self::PARAMETERS => $resultParameters,
            self::HINTS => $queryHints,
            self::HASH => $this->getEncryptedData($dql, $resultParameters, $queryHints),
        ];

        return $result;
    }

    private function checkCacheDataConsistency(array $data): bool
    {
        if (!empty($data[self::DQL]) &&
            !empty($data[self::HASH]) &&
            !empty($data[self::PARAMETERS])
        ) {
            $hash = $this->getDecryptedData($data[self::HASH]);

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

        return $this->getEncryptedHash($data);
    }

    private function getDecryptedData(string $hash): ?string
    {
        return $this->crypter->decryptData($hash);
    }

    private function getHashData(string $dql, array $parameters = [], array $queryHints = []): string
    {
        return md5(serialize([
            self::DQL => $dql,
            self::PARAMETERS => $parameters,
            self::HINTS => $queryHints,
        ]));
    }

    private function getEncryptedHash(string $data): ?string
    {
        return $this->crypter->encryptData($data);
    }
}
