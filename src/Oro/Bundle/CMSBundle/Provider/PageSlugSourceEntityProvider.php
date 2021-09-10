<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\RedirectBundle\Provider\SluggableEntityFinder;
use Oro\Bundle\RedirectBundle\Provider\SlugSourceEntityProviderInterface;

/**
 * Provides Page source entity for the slug.
 */
class PageSlugSourceEntityProvider implements SlugSourceEntityProviderInterface
{
    private SluggableEntityFinder $sluggableEntityFinder;
    private ConfigManager $configManager;

    public function __construct(SluggableEntityFinder $sluggableEntityFinder, ConfigManager $configManager)
    {
        $this->sluggableEntityFinder = $sluggableEntityFinder;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceEntityBySlug(Slug $slug): ?SlugAwareInterface
    {
        if (!$this->configManager->get(Configuration::getConfigKey(Configuration::ENABLE_DIRECT_URL))) {
            return null;
        }

        return $this->sluggableEntityFinder->findEntityBySlug(Page::class, $slug);
    }
}
