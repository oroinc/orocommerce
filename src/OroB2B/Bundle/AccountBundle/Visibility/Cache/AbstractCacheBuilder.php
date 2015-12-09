<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Visibility\Calculator\CategoryVisibilityResolverAdapterInterface;

abstract class AbstractCacheBuilder implements CacheBuilderInterface
{
    /** @var  ManagerRegistry */
    protected $registry;

    /** @var  CategoryVisibilityResolverAdapterInterface */
    protected $categoryVisibilityResolver;

    /** @var  ConfigManager */
    protected $configManager;

    /**
     * @param ManagerRegistry $registry
     * @param CategoryVisibilityResolverAdapterInterface $categoryVisibilityResolver
     * @param ConfigManager $configManager
     */
    public function __construct(
        ManagerRegistry $registry,
        CategoryVisibilityResolverAdapterInterface $categoryVisibilityResolver,
        ConfigManager $configManager
    ) {
        $this->registry = $registry;
        $this->categoryVisibilityResolver = $categoryVisibilityResolver;
        $this->configManager = $configManager;
    }

    /**
     * @param BaseProductVisibilityResolved $productVisibilityResolved
     * @param VisibilityInterface $productVisibility
     * @param string $selectedVisibility
     */
    protected function resolveStaticValues(
        BaseProductVisibilityResolved $productVisibilityResolved,
        VisibilityInterface $productVisibility,
        $selectedVisibility
    ) {
        $productVisibilityResolved->setSourceProductVisibility($productVisibility);
        $productVisibilityResolved->setSource(BaseProductVisibilityResolved::SOURCE_STATIC);
        $productVisibilityResolved->setCategoryId(null);
        if ($selectedVisibility === VisibilityInterface::VISIBLE) {
            $productVisibilityResolved->setVisibility(BaseProductVisibilityResolved::VISIBILITY_VISIBLE);
        } elseif ($selectedVisibility === VisibilityInterface::HIDDEN) {
            $productVisibilityResolved->setVisibility(BaseProductVisibilityResolved::VISIBILITY_HIDDEN);
        }
    }

    /**
     * @param BaseProductVisibilityResolved $productVisibilityResolved
     * @param VisibilityInterface|null $productVisibility
     */
    protected function resolveConfigValue(
        BaseProductVisibilityResolved $productVisibilityResolved,
        VisibilityInterface $productVisibility = null
    ) {
        $productVisibilityResolved->setSourceProductVisibility($productVisibility);
        $productVisibilityResolved->setSource(BaseProductVisibilityResolved::SOURCE_STATIC);
        $productVisibilityResolved->setCategoryId(null);
        $productVisibilityResolved->setVisibility($this->getVisibilityFromConfig());
    }

    /**
     * @return int
     */
    protected function getVisibilityFromConfig()
    {
        $visibilityFromConfig = $this->configManager->get('oro_b2b_account.product_visibility');
        $visibility = $visibilityFromConfig === VisibilityInterface::VISIBLE ? 1 : -1;

        return $visibility;
    }
}
