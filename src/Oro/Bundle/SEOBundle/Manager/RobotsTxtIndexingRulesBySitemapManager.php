<?php

namespace Oro\Bundle\SEOBundle\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProviderRegistryInterface;
use Oro\Component\Website\WebsiteInterface;

/**
 * Adds rules to robots.txt file according to system configuration.
 */
class RobotsTxtIndexingRulesBySitemapManager
{
    private const ALLOW      = 'Allow';
    private const DISALLOW   = 'Disallow';
    private const USER_AGENT = 'User-Agent';

    private const AUTO_GENERATED_MARK = '# auto-generated';

    /** @var RobotsTxtFileManager */
    private $robotsTxtFileManager;

    /** @var ConfigManager */
    private $configManager;

    /** @var UrlItemsProviderRegistryInterface */
    private $urlItemsProviderRegistry;

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
        $content = $this->robotsTxtFileManager->getContent($website);
        $content = explode(PHP_EOL, $content);
        $regex = '/^\s*(%s|%s|%s)\s*:\s*(\/?.+)%s\s*$/i';
        $lineRegex = sprintf(
            $regex,
            self::ALLOW,
            self::DISALLOW,
            self::USER_AGENT,
            self::AUTO_GENERATED_MARK
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
                self::AUTO_GENERATED_MARK
            );
            $providers = $this->urlItemsProviderRegistry->getProvidersIndexedByNames();
            foreach ($providers as $providerType => $provider) {
                foreach ($provider->getUrlItems($website, $version) as $urlItem) {
                    $allowUrl = parse_url($urlItem->getLocation(), PHP_URL_PATH);
                    $content[] = sprintf(
                        '%s: %s %s',
                        self::ALLOW,
                        $allowUrl,
                        self::AUTO_GENERATED_MARK
                    );
                }
            }
            $content[] = sprintf(
                '%s: / %s',
                self::DISALLOW,
                self::AUTO_GENERATED_MARK
            );
        }

        $this->robotsTxtFileManager->dumpContent(implode(PHP_EOL, $content), $website);
    }
}
