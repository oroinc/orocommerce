<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\RedirectBundle\Provider\SluggableEntityFinder;
use Oro\Bundle\RedirectBundle\Provider\SlugSourceEntityProviderInterface;

/**
 * Provides Category source entity for the slug.
 */
class CategorySlugSourceEntityProvider implements SlugSourceEntityProviderInterface
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

        return $this->sluggableEntityFinder->findEntityBySlug(Category::class, $slug);
    }
}
