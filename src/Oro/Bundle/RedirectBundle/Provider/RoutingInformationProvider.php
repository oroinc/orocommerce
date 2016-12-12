<?php

namespace Oro\Bundle\RedirectBundle\Provider;

use Oro\Bundle\RedirectBundle\Exception\UnsupportedEntityException;

class RoutingInformationProvider implements RoutingInformationProviderInterface
{
    /**
     * @var array|RoutingInformationProviderInterface[]
     */
    protected $providers = [];

    /**
     * @param RoutingInformationProviderInterface $provider
     */
    public function registerProvider(RoutingInformationProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported($entity)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isSupported($entity)) {
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
     * @param object $entity
     * @return null|RoutingInformationProviderInterface
     * @throws UnsupportedEntityException
     */
    protected function getProviderForEntity($entity)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isSupported($entity)) {
                return $provider;
            }
        }

        throw new UnsupportedEntityException();
    }
}
