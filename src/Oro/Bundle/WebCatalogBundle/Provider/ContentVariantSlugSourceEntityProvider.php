<?php

namespace Oro\Bundle\WebCatalogBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\RedirectBundle\Provider\SlugSourceEntityProviderInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentVariantRepository;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

/**
 * Provides ContentVariant source entity for the slug.
 */
class ContentVariantSlugSourceEntityProvider implements SlugSourceEntityProviderInterface
{
    use FeatureCheckerHolderTrait;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var WebsiteManager
     */
    protected $websiteManager;

    public function __construct(ManagerRegistry $registry, WebsiteManager $websiteManager)
    {
        $this->registry = $registry;
        $this->websiteManager = $websiteManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceEntityBySlug(Slug $slug): ?SlugAwareInterface
    {
        $website = $this->websiteManager->getCurrentWebsite();
        if (!$website || $this->isFeaturesEnabled($website)) {
            return null;
        }

        /** @var ContentVariantRepository $repository */
        $repository = $this->registry
            ->getManagerForClass(ContentVariant::class)
            ->getRepository(ContentVariant::class);

        return $repository->findOneBySlug($slug);
    }
}
