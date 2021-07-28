<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\RedirectBundle\Provider\SluggableEntityFinder;
use Oro\Bundle\RedirectBundle\Provider\SlugSourceEntityProviderInterface;

/**
 * Provides Brand source entity for the slug.
 */
class BrandSlugSourceEntityProvider implements SlugSourceEntityProviderInterface
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

        return $this->sluggableEntityFinder->findEntityBySlug(Brand::class, $slug);
    }
}
