<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\AccountBundle\Entity\Repository\ProductResolvedRepositoryTrait;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Visibility\Resolver\CategoryVisibilityResolverInterface;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;

abstract class AbstractResolvedCacheBuilder implements ProductCaseCacheBuilderInterface
{
    /** @var  ManagerRegistry */
    protected $registry;

    /** @var  CategoryVisibilityResolverInterface */
    protected $categoryVisibilityResolver;

    /** @var  ConfigManager */
    protected $configManager;

    /**
     * @param ManagerRegistry $registry
     * @param CategoryVisibilityResolverInterface $categoryVisibilityResolver
     * @param ConfigManager $configManager
     */
    public function __construct(
        ManagerRegistry $registry,
        CategoryVisibilityResolverInterface $categoryVisibilityResolver,
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
     * @return array
     */
    protected function resolveStaticValues(VisibilityInterface $productVisibility, $selectedVisibility)
    {
        $updateData = [
            'sourceProductVisibility' => $productVisibility,
            'source' => BaseProductVisibilityResolved::SOURCE_STATIC,
            'category' => null,
        ];

        if ($selectedVisibility === VisibilityInterface::VISIBLE) {
            $updateData['visibility'] = BaseProductVisibilityResolved::VISIBILITY_VISIBLE;
        } elseif ($selectedVisibility === VisibilityInterface::HIDDEN) {
            $updateData['visibility'] = BaseProductVisibilityResolved::VISIBILITY_HIDDEN;
        }

        return $updateData;
    }

    /**
     * @param VisibilityInterface|null $productVisibility
     * @return array
     */
    protected function resolveConfigValue(VisibilityInterface $productVisibility = null)
    {
        return [
            'sourceProductVisibility' => $productVisibility,
            'visibility' => $this->getVisibilityFromConfig(),
            'source' => BaseProductVisibilityResolved::SOURCE_STATIC,
            'category' => null,
        ];
    }

    /**
     * @param EntityRepository|ProductResolvedRepositoryTrait $repository
     * @param bool $insert
     * @param bool $delete
     * @param array $update
     * @param array $where
     */
    protected function executeDbQuery(EntityRepository $repository, $insert, $delete, array $update, array $where)
    {
        if ($insert) {
            $repository->insertEntity(array_merge($update, $where));
        } elseif ($delete) {
            $repository->deleteEntity($where);
        } elseif ($update) {
            $repository->updateEntity($update, $where);
        }
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
