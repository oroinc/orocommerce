<?php

namespace Oro\Bundle\RedirectBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\RedirectBundle\Exception\UnsupportedEntityException;
use Psr\Container\ContainerInterface;

/**
 * Delegates the getting of routing information to child providers.
 */
class RoutingInformationProvider implements RoutingInformationProviderInterface
{
    /** @var string[] */
    private $entityClasses = [];

    /** @var ContainerInterface */
    private $providers;

    /** @var array */
    private $providerByClass = [];

    /**
     * @param string[] $entityClasses
     * @param ContainerInterface $providers
     */
    public function __construct(array $entityClasses, ContainerInterface $providers)
    {
        $this->entityClasses = $entityClasses;
        $this->providers = $providers;
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported($entity)
    {
        foreach ($this->entityClasses as $entityClass) {
            if ($this->getProvider($entityClass)->isSupported($entity)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteData($entity)
    {
        return $this->getProviderForEntity($entity)->getRouteData($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getUrlPrefix($entity)
    {
        return $this->getProviderForEntity($entity)->getUrlPrefix($entity);
    }

    /**
     * @return string[]
     */
    public function getEntityClasses()
    {
        return $this->entityClasses;
    }

    private function getProvider(string $entityClass): RoutingInformationProviderInterface
    {
        return $this->providers->get($entityClass);
    }

    /**
     * @param object $entity
     *
     * @return RoutingInformationProviderInterface|null
     *
     * @throws UnsupportedEntityException
     */
    private function getProviderForEntity($entity): ?RoutingInformationProviderInterface
    {
        $entityClass = ClassUtils::getClass($entity);
        if (array_key_exists($entityClass, $this->providerByClass)) {
            return $this->providerByClass[$entityClass];
        }

        foreach ($this->entityClasses as $entityClass) {
            $provider = $this->getProvider($entityClass);
            if ($provider->isSupported($entity)) {
                $this->providerByClass[$entityClass] = $provider;

                return $provider;
            }
        }

        throw new UnsupportedEntityException();
    }
}
