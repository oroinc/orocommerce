<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache;


use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Visibility\Calculator\CategoryVisibilityResolverAdapterInterface;

use Symfony\Bridge\Doctrine\RegistryInterface;

abstract class AbstractCacheBuilder
{
    /** @var  RegistryInterface */
    protected $registry;

    /** @var  CategoryVisibilityResolverAdapterInterface */
    protected $categoryVisibilityResolver;

    /** @var  ConfigManager */
    protected $configManager;

    /**
     * @param RegistryInterface $registry
     * @param CategoryVisibilityResolverAdapterInterface $categoryVisibilityResolver
     * @param ConfigManager $configManager
     */
    public function __construct(
        RegistryInterface $registry,
        CategoryVisibilityResolverAdapterInterface $categoryVisibilityResolver,
        ConfigManager $configManager
    ) {
        $this->registry = $registry;
        $this->categoryVisibilityResolver = $categoryVisibilityResolver;
        $this->configManager = $configManager;
    }

    /**
     * @param VisibilityInterface $productVisibility
     * @param BaseProductVisibilityResolved $productVisibilityResolved
     * @param string $selectedVisibility
     */
    protected function resolveStaticValues(
        VisibilityInterface $productVisibility,
        BaseProductVisibilityResolved $productVisibilityResolved,
        $selectedVisibility
    ) {
        $productVisibilityResolved->setSourceProductVisibility($productVisibility);
        $productVisibilityResolved->setSource(BaseProductVisibilityResolved::SOURCE_STATIC);
        $productVisibilityResolved->setCategoryId(null);
        if ($selectedVisibility == VisibilityInterface::VISIBLE) {
            $productVisibilityResolved->setVisibility(BaseProductVisibilityResolved::VISIBILITY_VISIBLE);
        } elseif ($selectedVisibility == VisibilityInterface::HIDDEN) {
            $productVisibilityResolved->setVisibility(BaseProductVisibilityResolved::VISIBILITY_HIDDEN);
        }
    }

    /**
     * @param ConfigManager $configManager
     * @return int
     */
    protected function getVisibilityFromConfig(ConfigManager $configManager)
    {
        $visibilityFromConfig = $configManager->get('oro_b2b_account.product_visibility');
        $visibility = $visibilityFromConfig == VisibilityInterface::VISIBLE ? 1 : -1;

        return $visibility;
    }
}
