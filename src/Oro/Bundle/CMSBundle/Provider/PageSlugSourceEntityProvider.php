<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Entity\Repository\PageRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\RedirectBundle\Provider\SlugSourceEntityProviderInterface;

/**
 * Provides Page source entity for the slug.
 */
class PageSlugSourceEntityProvider implements SlugSourceEntityProviderInterface
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

        /** @var PageRepository $repository */
        $repository = $this->registry->getManagerForClass(Page::class)->getRepository(Page::class);

        return $repository->findOneBySlug($slug);
    }
}
