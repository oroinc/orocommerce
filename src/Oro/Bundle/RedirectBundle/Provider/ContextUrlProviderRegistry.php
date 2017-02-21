<?php

namespace Oro\Bundle\RedirectBundle\Provider;

class ContextUrlProviderRegistry
{
    /**
     * @var array|ContextUrlProviderInterface[]
     */
    private $providers = [];

    /**
     * @param ContextUrlProviderInterface $provider
     * @param string $type
     */
    public function registerProvider(ContextUrlProviderInterface $provider, $type)
    {
        $this->providers[$type] = $provider;
    }

    /**
     * @param string $type
     * @param mixed $data
     * @return null|string
     */
    public function getUrl($type, $data)
    {
        if (array_key_exists($type, $this->providers)) {
            return $this->providers[$type]->getUrl($data);
        }

        return null;
    }
}
