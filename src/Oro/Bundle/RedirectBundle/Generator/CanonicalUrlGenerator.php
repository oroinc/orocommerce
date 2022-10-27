<?php

namespace Oro\Bundle\RedirectBundle\Generator;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProvider;
use Oro\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Generates Canonical URLs based on the given website and website configuration.
 */
class CanonicalUrlGenerator
{
    private RoutingInformationProvider $routingInformationProvider;
    private ConfigManager $configManager;
    private RequestStack $requestStack;
    private WebsiteUrlResolver $websiteSystemUrlResolver;
    private CacheInterface $cache;
    private LocalizationProviderInterface $localizationProvider;

    public function __construct(
        ConfigManager $configManager,
        CacheInterface $cache,
        RequestStack $requestStack,
        RoutingInformationProvider $routingInformationProvider,
        WebsiteUrlResolver $websiteSystemUrlResolver,
        LocalizationProviderInterface $localizationProvider
    ) {
        $this->configManager = $configManager;
        $this->cache = $cache;
        $this->requestStack = $requestStack;
        $this->routingInformationProvider = $routingInformationProvider;
        $this->websiteSystemUrlResolver = $websiteSystemUrlResolver;
        $this->localizationProvider = $localizationProvider;
    }

    /**
     * @param SluggableInterface $entity
     * @param Localization|null $localization
     * @param WebsiteInterface|null $website
     *
     * @return string
     */
    public function getUrl(
        SluggableInterface $entity,
        Localization $localization = null,
        WebsiteInterface $website = null
    ) {
        $url = '';

        if ($this->isDirectUrlEnabled($website)) {
            $url = $this->getDirectUrl($entity, $localization, $website);
        }

        if (!$url) {
            $url = $this->getSystemUrl($entity, $website);
        }

        return $url;
    }

    /**
     * @param SlugAwareInterface $entity
     * @param Localization|null $localization
     * @param WebsiteInterface|null $website
     *
     * @return string
     */
    public function getDirectUrl(
        SlugAwareInterface $entity,
        Localization $localization = null,
        WebsiteInterface $website = null
    ) {
        $url = '';
        $slug = $this->getDirectUrlSlug($entity, $localization);
        if ($slug) {
            $slugUrl = $slug->getUrl();
            $url = $this->getAbsoluteUrl($slugUrl, $website);
        }

        return $url;
    }

    public function getCanonicalDomainUrl(WebsiteInterface $website = null): ?string
    {
        if ($this->getCanonicalUrlSecurityType($website) === Configuration::SECURE) {
            return $this->websiteSystemUrlResolver->getWebsiteSecureUrl($website);
        }

        return $this->websiteSystemUrlResolver->getWebsiteUrl($website);
    }

    /**
     * @param string $slugUrl
     * @param WebsiteInterface|null $website
     *
     * @return string
     */
    public function getAbsoluteUrl($slugUrl, WebsiteInterface $website = null)
    {
        return $this->createUrl($this->getCanonicalDomainUrl($website), $slugUrl);
    }

    /**
     * @param SluggableInterface $entity
     * @param WebsiteInterface|null $website
     *
     * @return string
     */
    public function getSystemUrl(SluggableInterface $entity, WebsiteInterface $website = null)
    {
        $routeData = $this->routingInformationProvider->getRouteData($entity);

        if ($this->getCanonicalUrlSecurityType($website) === Configuration::SECURE) {
            $url = $this->websiteSystemUrlResolver->getWebsiteSecurePath(
                $routeData->getRoute(),
                $routeData->getRouteParameters(),
                $website
            );
        } else {
            $url = $this->websiteSystemUrlResolver->getWebsitePath(
                $routeData->getRoute(),
                $routeData->getRouteParameters(),
                $website
            );
        }

        return $url;
    }

    /**
     * @param WebsiteInterface|null $website
     * @return bool
     */
    public function isDirectUrlEnabled(WebsiteInterface $website = null)
    {
        return $this->getCanonicalUrlType($website) === Configuration::DIRECT_URL;
    }

    /**
     * @param WebsiteInterface|null $website
     *
     * @return string
     */
    public function getCanonicalUrlType(WebsiteInterface $website = null)
    {
        return $this->getCachedConfigValue(Configuration::CANONICAL_URL_TYPE, $website);
    }

    /**
     * @param WebsiteInterface|null $website
     *
     * @return string
     */
    public function getCanonicalUrlSecurityType(WebsiteInterface $website = null)
    {
        return $this->getCachedConfigValue(Configuration::CANONICAL_URL_SECURITY_TYPE, $website);
    }

    public function clearCache(WebsiteInterface $website = null)
    {
        $this->cache->delete($this->getCacheKey(
            $this->getConfigKey(Configuration::CANONICAL_URL_TYPE),
            $website
        ));
        $this->cache->delete($this->getCacheKey(
            $this->getConfigKey(Configuration::CANONICAL_URL_SECURITY_TYPE),
            $website
        ));
        $this->cache->delete($this->getCacheKey($this->getConfigKey(Configuration::USE_LOCALIZED_CANONICAL)));
    }

    private function getCachedConfigValue($key, WebsiteInterface $website = null) : mixed
    {
        $configKey = $this->getConfigKey($key);
        $cacheKey = $this->getCacheKey($configKey, $website);
        return $this->cache->get($cacheKey, function () use ($configKey, $website) {
            return $this->configManager->get($configKey, false, false, $website);
        });
    }

    /**
     * @param SlugAwareInterface $entity
     * @param Localization|null $localization
     *
     * @return null|Slug
     */
    protected function getDirectUrlSlug(SlugAwareInterface $entity, Localization $localization = null)
    {
        if ($localization === null) {
            $localization = $this->getLocalization();
        }

        if ($localization) {
            $slug = $entity->getSlugByLocalization($localization);
            if ($slug) {
                return $slug;
            }
        }

        return $entity->getBaseSlug();
    }

    /**
     * @param string $domainUrl
     * @param string $url
     * @return string
     */
    public function createUrl($domainUrl, $url)
    {
        $domainUrl = rtrim($domainUrl, ' /');
        $baseUrl = '';
        if ($masterRequest = $this->requestStack->getMainRequest()) {
            $baseUrl = $masterRequest->getBaseUrl();
            $baseUrl = trim($baseUrl, '/');
        }

        if ($baseUrl) {
            $domainUrl = str_replace(parse_url($domainUrl, PHP_URL_PATH), '', $domainUrl);
            $urlParts = [rtrim($domainUrl, ' /'), $baseUrl];
        } else {
            $urlParts = [$domainUrl];
        }

        $urlParts[] = ltrim($url, '/');

        return implode('/', $urlParts);
    }

    /**
     * @param string $configField
     * @return string
     */
    private function getConfigKey($configField)
    {
        return Configuration::ROOT_NODE . '.' . $configField;
    }

    private function getCacheKey(string $configKey, WebsiteInterface $website = null) : string
    {
        $cacheKey  = $website ? sprintf('%s.%s', $configKey, $website->getId()) : $configKey;
        return UniversalCacheKeyGenerator::normalizeCacheKey($cacheKey);
    }

    private function isLocalizedCanonicalUrlsEnabled(): bool
    {
        return (bool)$this->getCachedConfigValue(Configuration::USE_LOCALIZED_CANONICAL);
    }

    private function getLocalization(): ?Localization
    {
        if ($this->isLocalizedCanonicalUrlsEnabled()) {
            return $this->localizationProvider->getCurrentLocalization();
        }

        return null;
    }
}
