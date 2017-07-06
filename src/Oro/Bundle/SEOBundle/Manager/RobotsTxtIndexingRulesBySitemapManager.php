<?php

namespace Oro\Bundle\SEOBundle\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProviderRegistryInterface;
use Oro\Component\Website\WebsiteInterface;

class RobotsTxtIndexingRulesBySitemapManager
{
    const ALLOW = 'Allow';
    const DISALLOW = 'Disallow';
    const USER_AGENT = 'User-Agent';

    /**
     * @var RobotsTxtFileManager
     */
    private $robotsTxtFileManager;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var UrlItemsProviderRegistryInterface
     */
    private $urlItemsProviderRegistry;

    /**
     * @param RobotsTxtFileManager              $robotsTxtFileManager
     * @param ConfigManager                     $configManager
     * @param UrlItemsProviderRegistryInterface $urlItemsProviderRegistry
     */
    public function __construct(
        RobotsTxtFileManager $robotsTxtFileManager,
        ConfigManager $configManager,
        UrlItemsProviderRegistryInterface $urlItemsProviderRegistry
    ) {
        $this->robotsTxtFileManager = $robotsTxtFileManager;
        $this->configManager = $configManager;
        $this->urlItemsProviderRegistry = $urlItemsProviderRegistry;
    }

    /**
     * @param WebsiteInterface $website
     * @param string           $version
     */
    public function flush(WebsiteInterface $website, $version)
    {
        $content = $this->robotsTxtFileManager->getContent();
        $content = explode(PHP_EOL, $content);
        $regex = '/^\s*(%s|%s|%s)\s*:\s*(\/?.+)%s\s*$/i';
        $lineRegex = sprintf(
            $regex,
            self::ALLOW,
            self::DISALLOW,
            self::USER_AGENT,
            RobotsTxtFileManager::AUTO_GENERATED_MARK
        );
        foreach ($content as $key => $line) {
            if (preg_match($lineRegex, $line)) {
                unset($content[$key]);
            }
        }
        if (!$this->configManager->get('oro_frontend.guest_access_enabled', false, false, $website)) {
            $content[] = sprintf(
                '%s: * %s',
                self::USER_AGENT,
                RobotsTxtFileManager::AUTO_GENERATED_MARK
            );
            $providers = $this->urlItemsProviderRegistry->getProvidersIndexedByNames();
            foreach ($providers as $providerType => $provider) {
                foreach ($provider->getUrlItems($website, $version) as $urlItem) {
                    $allowUrl = parse_url($urlItem->getLocation(), PHP_URL_PATH);
                    $content[] = sprintf(
                        '%s: %s %s',
                        self::ALLOW,
                        $allowUrl,
                        RobotsTxtFileManager::AUTO_GENERATED_MARK
                    );
                }
            }
            $content[] = sprintf(
                '%s: / %s',
                self::DISALLOW,
                RobotsTxtFileManager::AUTO_GENERATED_MARK
            );
        }

        $this->robotsTxtFileManager->dumpContent(implode(PHP_EOL, $content));
    }
}
