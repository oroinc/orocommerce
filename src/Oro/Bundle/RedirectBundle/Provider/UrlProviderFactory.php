<?php

namespace Oro\Bundle\RedirectBundle\Provider;

class UrlProviderFactory
{
    /**
     * @var array|SluggableUrlProviderInterface[]
     */
    protected $providers = [];

    /**
     * @var string
     */
    protected $currentType;

    /**
     * @param string $currentCacheType
     */
    public function __construct($currentCacheType)
    {
        $this->currentType = $currentCacheType;
    }

    /**
     * @param string $type
     * @param SluggableUrlProviderInterface $provider
     */
    public function registerProvider($type, SluggableUrlProviderInterface $provider)
    {
        $this->providers[$type] = $provider;
    }

    /**
     * @return SluggableUrlProviderInterface
     */
    public function get()
    {
        if (!array_key_exists($this->currentType, $this->providers)) {
            throw new \RuntimeException(
                sprintf(
                    'There is no UrlProvider registered for type %s. Known types: %s',
                    $this->currentType,
                    implode(', ', array_keys($this->providers))
                )
            );
        }

        return $this->providers[$this->currentType];
    }
}
