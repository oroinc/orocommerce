<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ActionConfigurationProvider
{
    const ROOT_NODE_NAME = 'actions';

    /** @var CacheProvider */
    protected $cache;

    /** @var array */
    protected $rawConfiguration;

    /** @var array */
    protected $kernelBundles;

    /**
     * @param CacheProvider $cache
     * @param array $rawConfiguration
     * @param array $kernelBundles
     */
    public function __construct(CacheProvider $cache, array $rawConfiguration, array $kernelBundles)
    {
        $this->cache = $cache;
        $this->rawConfiguration = $rawConfiguration;
        $this->kernelBundles = array_values($kernelBundles);
    }

    /**
     * @return array
     * @throws InvalidConfigurationException
     */
    public function getActionConfiguration()
    {
        if ($this->cache->contains(self::ROOT_NODE_NAME)) {
            $configuration = $this->cache->fetch(self::ROOT_NODE_NAME);
        } else {
            $configuration = $this->prepareRawConfiguration();

            $this->cache->deleteAll();
            $this->cache->save(self::ROOT_NODE_NAME, $configuration);
        }

        return $configuration;
    }

    /**
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function prepareRawConfiguration()
    {
        $actionConfigs = [];

        foreach ($this->rawConfiguration as $bundle => $actions) {
            $bundleNumber = array_search($bundle, $this->kernelBundles, true);

            if ($bundleNumber === false) {
                continue;
            }

            foreach ($actions as $actionName => $config) {
                if (array_key_exists($actionName, $actionConfigs)) {
                    $actionConfigs[$actionName][$bundleNumber] = $config;
                } else {
                    $actionConfigs[$actionName] = [$bundleNumber => $config];
                }
            }
        }

        foreach ($actionConfigs as $actionName => $configs) {
            $actionConfigs[$actionName] = $this->mergeActionConfigs($configs);
        }

        try {
            $data = [];
            if (!empty($actionConfigs)) {
                $data = $actionConfigs;
                //$data = $this->configuration->processConfiguration($actionConfigs);
            }
        } catch (InvalidConfigurationException $exception) {
            throw new InvalidConfigurationException(
                sprintf('Can\'t parse process configuration. %s', $exception->getMessage())
            );
        }

        return $data;
    }

    /**
     * @param array $configs
     * @return array
     */
    protected function mergeActionConfigs(array $configs)
    {
        return $configs;
    }
}
