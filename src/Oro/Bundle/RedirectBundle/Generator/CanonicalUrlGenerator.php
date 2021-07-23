<?php

namespace Oro\Bundle\RedirectBundle\Generator;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\DependencyInjection\OroRedirectExtension;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProvider;
use Oro\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Generates Canonical URLs based on the given website and website configuration.
 */
class CanonicalUrlGenerator
{
    /**
     * @var RoutingInformationProvider
     */
    protected $routingInformationProvider;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var WebsiteUrlResolver
     */
    protected $websiteSystemUrlResolver;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var LocalizationProviderInterface
     */
    private $localizationProvider;

    public function __construct(
        ConfigManager $configManager,
        Cache $cache,
        RequestStack $requestStack,
        RoutingInformationProvider $routingInformationProvider,
        WebsiteUrlResolver $websiteSystemUrlResolver
    ) {
        $this->configManager = $configManager;
        $this->cache = $cache;
        $this->requestStack = $requestStack;
        $this->routingInformationProvider = $routingInformationProvider;
        $this->websiteSystemUrlResolver = $websiteSystemUrlResolver;
    }

    public function setLocalizationProvider(LocalizationProviderInterface $localizationProvider)
    {
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

    private function getCachedConfigValue($key, WebsiteInterface $website = null)
    {
        $configKey = $this->getConfigKey($key);
        $cacheKey = $this->getCacheKey($configKey, $website);
        if (!$this->cache->contains($cacheKey)) {
            $this->cache->save($cacheKey, $this->configManager->get($configKey, false, false, $website));
        }

        return $this->cache->fetch($cacheKey);
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
        if ($masterRequest = $this->requestStack->getMasterRequest()) {
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
        return sprintf('%s.%s', OroRedirectExtension::ALIAS, $configField);
    }

    /**
     * @param string $configKey
     * @param WebsiteInterface|null $website
     * @return string
     */
    private function getCacheKey($configKey, WebsiteInterface $website = null)
    {
        return $website ? sprintf('%s.%s', $configKey, $website->getId()) : $configKey;
    }

    private function isLocalizedCanonicalUrlsEnabled(): bool
    {
        return (bool)$this->getCachedConfigValue(Configuration::USE_LOCALIZED_CANONICAL);
    }

    private function getLocalization(): ?Localization
    {
        if ($this->localizationProvider && $this->isLocalizedCanonicalUrlsEnabled()) {
            return $this->localizationProvider->getCurrentLocalization();
        }

        return null;
    }
}
