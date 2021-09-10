<?php

namespace Oro\Bundle\WebCatalogBundle\Provider;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\RedirectBundle\Provider\SluggableEntityFinder;
use Oro\Bundle\RedirectBundle\Provider\SlugSourceEntityProviderInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * Provides ContentVariant source entity for the slug.
 */
class ContentVariantSlugSourceEntityProvider implements SlugSourceEntityProviderInterface
{
    use FeatureCheckerHolderTrait;

    private SluggableEntityFinder $sluggableEntityFinder;
    private WebsiteManager $websiteManager;

    public function __construct(SluggableEntityFinder $sluggableEntityFinder, WebsiteManager $websiteManager)
    {
        $this->sluggableEntityFinder = $sluggableEntityFinder;
        $this->websiteManager = $websiteManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceEntityBySlug(Slug $slug): ?SlugAwareInterface
    {
        $website = $this->websiteManager->getCurrentWebsite();
        if (null === $website || $this->isFeaturesEnabled($website)) {
            return null;
        }

        return $this->sluggableEntityFinder->findEntityBySlug(ContentVariant::class, $slug);
    }
}
