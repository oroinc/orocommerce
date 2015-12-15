<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Visibility\Calculator\CategoryVisibilityResolver;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;

abstract class AbstractResolvedCacheBuilder implements ProductCaseCacheBuilderInterface
{
    /** @var  ManagerRegistry */
    protected $registry;

    /** @var  CategoryVisibilityResolver */
    protected $categoryVisibilityResolver;

    /** @var  ConfigManager */
    protected $configManager;

    /** @var  InsertFromSelectQueryExecutor */
    protected $insertFromSelectQueryExecutor;

    /**
     * @param ManagerRegistry $registry
     * @param CategoryVisibilityResolver $categoryVisibilityResolver
     * @param ConfigManager $configManager
     * @param InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
     */
    public function __construct(
        ManagerRegistry $registry,
        CategoryVisibilityResolver $categoryVisibilityResolver,
        ConfigManager $configManager,
        InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
    ) {
        $this->registry = $registry;
        $this->categoryVisibilityResolver = $categoryVisibilityResolver;
        $this->configManager = $configManager;
        $this->insertFromSelectQueryExecutor = $insertFromSelectQueryExecutor;
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

    /**
     * @param boolean $isVisible
     * @return integer
     */
    protected function convertVisibility($isVisible)
    {
        return $isVisible ? BaseProductVisibilityResolved::VISIBILITY_VISIBLE
            : BaseProductVisibilityResolved::VISIBILITY_HIDDEN;
    }
}
