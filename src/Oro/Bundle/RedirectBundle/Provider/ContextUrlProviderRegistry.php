<?php

namespace Oro\Bundle\RedirectBundle\Provider;

use Psr\Container\ContainerInterface;

/**
 * The registry of context url providers.
 */
class ContextUrlProviderRegistry
{
    /** @var ContainerInterface */
    private $providers;

    public function __construct(ContainerInterface $providers)
    {
        $this->providers = $providers;
    }

    /**
     * @param string $type
     * @param mixed $data
     * @return null|string
     */
    public function getUrl(string $type, $data)
    {
        if (!$this->providers->has($type)) {
            return null;
        }

        /** @var ContextUrlProviderInterface $provider */
        $provider = $this->providers->get($type);

        return $provider->getUrl($data);
    }
}
