<?php

namespace Oro\Bundle\WebsiteBundle\Resolver;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class WebsiteUrlResolver
{
    const CONFIG_URL = 'oro_website.url';
    const CONFIG_SECURE_URL = 'oro_website.secure_url';

    /**
     * @param ConfigManager $configManager
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(ConfigManager $configManager, UrlGeneratorInterface $urlGenerator)
    {
        $this->configManager = $configManager;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param Website|null $website
     * @return string|null
     */
    public function getWebsiteUrl(Website $website = null)
    {
        return $this->configManager->get(self::CONFIG_URL, false, false, $website);
    }

    /**
     * @param Website|null $website
     * @return string|null
     */
    public function getWebsiteSecureUrl(Website $website = null)
    {
        $url = null;
        if ($websiteSecureUrl = $this->getWebsiteScopeConfigValue(self::CONFIG_SECURE_URL, $website)) {
            $url = $websiteSecureUrl;
        } elseif ($websiteUrl = $this->getWebsiteScopeConfigValue(self::CONFIG_URL, $website)) {
            $url = $websiteUrl;
        } elseif ($secureUrl = $this->getDefaultConfigValue(self::CONFIG_SECURE_URL, $website)) {
            $url = $secureUrl;
        } else {
            $url = $this->getDefaultConfigValue(self::CONFIG_URL, $website);
        }

        return $url;
    }

    /**
     * @param string $route
     * @param array $routeParams
     * @param Website|null $website
     * @return string
     */
    public function getWebsitePath($route, array $routeParams, Website $website = null)
    {
        $url = $this->getWebsiteUrl($website);

        return $this->preparePath($url, $route, $routeParams);
    }

    /**
     * @param string $route
     * @param array $routeParams
     * @param Website|null $website
     * @return string
     */
    public function getWebsiteSecurePath($route, array $routeParams, Website $website = null)
    {
        $url = $this->getWebsiteSecureUrl($website);

        return $this->preparePath($url, $route, $routeParams);
    }

    /**
     * @param string $configKey
     * @param Website|null $website
     * @return null|string
     */
    protected function getWebsiteScopeConfigValue($configKey, Website $website = null)
    {
        $configValue = $this->configManager->get($configKey, false, true, $website);
        if (!empty($configValue['value']) && empty($configValue['use_parent_scope_value'])) {
            return $configValue['value'];
        }

        return null;
    }

    /**
     * @param string $configKey
     * @param Website|null $website
     * @return null|string
     */
    protected function getDefaultConfigValue($configKey, Website $website = null)
    {
        return $this->configManager->get($configKey, true, false, $website);
    }

    /**
     * @param string $url
     * @param string $route
     * @param array $routeParams
     * @return string
     */
    protected function preparePath($url, $route, array $routeParams)
    {
        return rtrim($url, '/') . $this->urlGenerator->generate($route, $routeParams);
    }
}
