<?php

namespace Oro\Bundle\RedirectBundle\Routing;

use Oro\Bundle\RedirectBundle\Cache\UrlDataStorage;
use Oro\Bundle\RedirectBundle\Cache\UrlStorageCache;
use Oro\Bundle\RedirectBundle\Provider\ContextUrlProviderRegistry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class SluggableUrlGenerator implements UrlGeneratorInterface
{
    const CONTEXT_DELIMITER = '_item';
    const CONTEXT_TYPE = 'context_type';
    const CONTEXT_DATA = 'context_data';

    /**
     * @var UrlGeneratorInterface
     */
    private $generator;

    /**
     * @var UrlStorageCache
     */
    private $cache;

    /**
     * @var ContextUrlProviderRegistry
     */
    private $contextUrlProvider;

    /**
     * @param UrlStorageCache $cache
     * @param ContextUrlProviderRegistry $contextUrlProvider
     */
    public function __construct(
        UrlStorageCache $cache,
        ContextUrlProviderRegistry $contextUrlProvider
    ) {
        $this->cache = $cache;
        $this->contextUrlProvider = $contextUrlProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        if ($referenceType === self::ABSOLUTE_PATH || $referenceType === false) {
            return $this->generateSluggableUrl($name, $parameters);
        }

        return $this->generator->generate($name, $parameters, $referenceType);
    }

    /**
     * @param string $name
     * @param mixed $parameters
     * @return string
     */
    private function generateSluggableUrl($name, $parameters)
    {
        $contextUrl = $this->getContextUrl($parameters);

        $url = null;
        $urlDataStorage = $this->getUrlDataStorage($name, $parameters);
        if ($urlDataStorage) {
            // For context aware URLs slug may be used as item part
            if ($contextUrl && $slug = $urlDataStorage->getSlug($parameters)) {
                $url = $slug;
            }

            // For URLs without context only full URL is acceptable
            if (!$url) {
                $url = $urlDataStorage->getUrl($parameters);
            }
        }

        // If no Slug based URL is available - generate URL with base generator logic
        if (!$url) {
            $url = $this->generator->generate($name, $parameters);
        }

        return $this->addContextUrl($url, $contextUrl);
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->generator->setContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->generator->getContext();
    }

    /**
     * @param UrlGeneratorInterface $generator
     */
    public function setBaseGenerator(UrlGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @param string $name
     * @param array $parameters
     * @return null|UrlDataStorage
     */
    private function getUrlDataStorage($name, $parameters)
    {
        return $this->cache->getUrlDataStorage($name, $parameters);
    }

    /**
     * @param array $parameters
     * @return string
     */
    private function getContextUrl(array &$parameters)
    {
        if (array_key_exists(self::CONTEXT_TYPE, $parameters) && array_key_exists(self::CONTEXT_DATA, $parameters)) {
            $contextType = $parameters[self::CONTEXT_TYPE];
            $contextData = $parameters[self::CONTEXT_DATA];
            unset($parameters[self::CONTEXT_TYPE], $parameters[self::CONTEXT_DATA]);

            return $this->contextUrlProvider->getUrl($contextType, $contextData);
        }

        return null;
    }

    /**
     * @param string $url
     * @param string $contextUrl
     * @return string
     */
    private function addContextUrl($url, $contextUrl)
    {
        $baseUrl = $this->getContext()->getBaseUrl();
        if ($baseUrl) {
            if (strpos($url, $baseUrl) === 0) {
                $url = substr($url, strlen($baseUrl));
            }
            $urlParts = [trim($baseUrl, '/')];
        }

        if ($contextUrl) {
            $urlParts[] = trim($contextUrl, '/');
            $urlParts[] = self::CONTEXT_DELIMITER;
        }
        $urlParts[] = ltrim($url, '/');

        return '/' . implode('/', $urlParts);
    }
}
