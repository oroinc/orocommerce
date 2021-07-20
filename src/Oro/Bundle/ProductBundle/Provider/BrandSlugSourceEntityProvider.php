<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Entity\Repository\BrandRepository;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\RedirectBundle\Provider\SlugSourceEntityProviderInterface;

/**
 * Provides Brand source entity for the slug.
 */
class BrandSlugSourceEntityProvider implements SlugSourceEntityProviderInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function __construct(ManagerRegistry $registry, ConfigManager $configManager)
    {
        $this->registry = $registry;
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

        /** @var BrandRepository $repository */
        $repository = $this->registry->getManagerForClass(Brand::class)->getRepository(Brand::class);

        return $repository->findOneBySlug($slug);
    }
}
