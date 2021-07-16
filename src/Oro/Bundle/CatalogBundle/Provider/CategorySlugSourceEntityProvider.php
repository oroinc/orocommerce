<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\RedirectBundle\Provider\SlugSourceEntityProviderInterface;

/**
 * Provides Category source entity for the slug.
 */
class CategorySlugSourceEntityProvider implements SlugSourceEntityProviderInterface
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

        /** @var CategoryRepository $repository */
        $repository = $this->registry->getManagerForClass(Category::class)->getRepository(Category::class);

        return $repository->findOneBySlug($slug);
    }
}
