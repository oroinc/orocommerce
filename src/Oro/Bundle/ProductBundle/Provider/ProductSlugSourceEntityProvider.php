<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\RedirectBundle\Provider\SlugSourceEntityProviderInterface;

/**
 * Provides Product source entity for the slug.
 */
class ProductSlugSourceEntityProvider implements SlugSourceEntityProviderInterface
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

        /** @var ProductRepository $repository */
        $repository = $this->registry->getManagerForClass(Product::class)->getRepository(Product::class);

        return $repository->findOneBySlug($slug);
    }
}
