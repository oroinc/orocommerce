<?php

namespace Oro\Bundle\SEOBundle\Sitemap\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides Switchable UrlItems for sitemap generation
 */
class SwitchableUrlItemsProvider extends UrlItemsProvider
{
    /**
     * @var string
     */
    protected $excludeProviderKey;

    private SwitchableUrlItemsProviderInterface $provider;

    public function __construct(
        CanonicalUrlGenerator $canonicalUrlGenerator,
        ConfigManager $configManager,
        EventDispatcherInterface $eventDispatcher,
        ManagerRegistry $registry
    ) {
        parent::__construct($canonicalUrlGenerator, $configManager, $eventDispatcher, $registry);
    }

    /**
     * @param string $excludeProviderKey
     */
    public function setExcludeProviderKey(string $excludeProviderKey)
    {
        $this->excludeProviderKey = $excludeProviderKey;
    }

    public function setProvider(SwitchableUrlItemsProviderInterface $provider): void
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrlItems(WebsiteInterface $website, $version)
    {
        if ($this->provider->isUrlItemsExcluded($website)) {
            return [];
        }

        return parent::getUrlItems($website, $version);
    }
}
