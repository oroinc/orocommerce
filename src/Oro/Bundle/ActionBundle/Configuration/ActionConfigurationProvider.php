<?php

namespace Oro\Bundle\ActionBundle\Configuration;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use Oro\Bundle\UIBundle\Tools\ArrayUtils;

class ActionConfigurationProvider
{
    const ROOT_NODE_NAME = 'actions';
    const EXTENDS_NODE_NAME = 'extends';
    const REPLACES_NODE_NAME = 'replace';

    /** @var ActionDefinitionListConfiguration */
    protected $definitionConfiguration;

    /** @var ActionDefinitionConfigurationValidator */
    protected $definitionConfigurationValidator;

    /** @var CacheProvider */
    protected $cache;

    /** @var array */
    protected $rawConfiguration;

    /** @var array */
    protected $kernelBundles;

    /** @var array */
    protected $processedConfigs = [];

    /**
     * @param ActionDefinitionListConfiguration $definitionConfiguration
     * @param CacheProvider $cache
     * @param array $rawConfiguration
     * @param array $kernelBundles
     */
    public function __construct(
        ActionDefinitionListConfiguration $definitionConfiguration,
        ActionDefinitionConfigurationValidator $definitionConfigurationValidator,
        CacheProvider $cache,
        array $rawConfiguration,
        array $kernelBundles
    ) {
        $this->definitionConfiguration = $definitionConfiguration;
        $this->definitionConfigurationValidator = $definitionConfigurationValidator;
        $this->cache = $cache;
        $this->rawConfiguration = $rawConfiguration;
        $this->kernelBundles = array_values($kernelBundles);
    }

    public function warmUpCache()
    {
        $this->clearCache();
        $this->cache->save(self::ROOT_NODE_NAME, $this->resolveConfiguration());
    }

    public function clearCache()
    {
        $this->cache->delete(self::ROOT_NODE_NAME);
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
            $configuration = $this->resolveConfiguration();

            $this->clearCache();
            $this->cache->save(self::ROOT_NODE_NAME, $configuration);
        }

        return $configuration;
    }

    /**
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function resolveConfiguration()
    {
        $configs = $this->prepareRawConfiguration();

        foreach ($configs as $actionName => $actionConfigs) {
            $data = array_shift($actionConfigs);

            foreach ($actionConfigs as $config) {
                $data = $this->merge($data, $config);
            }

            $configs[$actionName] = (array)$data;
        }

        foreach ($configs as $actionName => &$config) {
            $this->resolveExtends($configs, $config, $actionName);
        }

        try {
            $data = [];
            if (!empty($configs)) {
                $data = $this->definitionConfiguration->processConfiguration($configs);

                $this->definitionConfigurationValidator->validate($data);
            }
        } catch (InvalidConfigurationException $exception) {
            throw new InvalidConfigurationException(
                sprintf('Can\'t parse process configuration. %s', $exception->getMessage())
            );
        }

        return $data;
    }

    /**
     * @return array
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
                $actionConfigs[$actionName][$bundleNumber] = $config;
            }
        }

        return array_map(
            function ($configs) {
                ksort($configs);
                return $configs;
            },
            $actionConfigs
        );
    }

    /**
     * @param array $data
     * @param array $config
     * @return array
     */
    protected function merge(array $data, array $config)
    {
        $replaces = empty($config[self::REPLACES_NODE_NAME]) ? [] : (array)$config[self::REPLACES_NODE_NAME];
        unset($data[self::REPLACES_NODE_NAME], $config[self::REPLACES_NODE_NAME]);

        foreach ($replaces as $key) {
            if (empty($config[$key])) {
                unset($data[$key]);
            } else {
                $data[$key] = $config[$key];
                unset($config[$key]);
            }
        }

        foreach ($config as $key => $value) {
            if (is_int($key)) {
                $data[] = $value;
            } else {
                if (!array_key_exists($key, $data)) {
                    $data[$key] = $value;
                } else {
                    if (is_array($value)) {
                        $data[$key] = $this->merge($data[$key], $value);
                    } else {
                        $data[$key] = $value;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param array $configs
     * @param array $config
     * @param string $actionName
     * @throws InvalidConfigurationException
     */
    protected function resolveExtends(array &$configs, array &$config, $actionName)
    {
        $this->processedConfigs[] = $actionName;

        if (!array_key_exists(self::EXTENDS_NODE_NAME, $config) || empty($config[self::EXTENDS_NODE_NAME])) {
            return;
        }

        $extends = $config[self::EXTENDS_NODE_NAME];
        if (!array_key_exists($extends, $configs)) {
            throw new InvalidConfigurationException(
                sprintf('Could not found config of %s for dependant action %s.', $extends, $actionName)
            );
        }

        $extendsConfig = &$configs[$extends];
        if (array_key_exists(self::EXTENDS_NODE_NAME, $extendsConfig)) {
            if (in_array($extends, $this->processedConfigs, true)) {
                throw new InvalidConfigurationException(
                    sprintf('Found circular "extends" references %s and %s actions.', $extends, $actionName)
                );
            }

            $this->resolveExtends($configs, $extendsConfig, $extends);
        }

        $config = ArrayUtils::arrayMergeRecursiveDistinct($extendsConfig, $config);
        unset($config[self::EXTENDS_NODE_NAME]);
    }
}
