<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\BasicOperationRepositoryTrait;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Base class for visibility cache builders.
 */
abstract class AbstractResolvedCacheBuilder implements CacheBuilderInterface
{
    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $cacheClass;

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @var ProductIndexScheduler
     */
    protected $indexScheduler;

    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertExecutor;

    /**
     * @var ProductReindexManager
     */
    protected $productReindexManager;

    public function __construct(
        ManagerRegistry $registry,
        ScopeManager $scopeManager,
        ProductIndexScheduler $indexScheduler,
        InsertFromSelectQueryExecutor $insertExecutor,
        ProductReindexManager $productReindexManager
    ) {
        $this->registry = $registry;
        $this->scopeManager = $scopeManager;
        $this->indexScheduler = $indexScheduler;
        $this->insertExecutor = $insertExecutor;
        $this->productReindexManager = $productReindexManager;
    }

    /**
     * @param string $cacheClass
     */
    public function setCacheClass($cacheClass)
    {
        $this->cacheClass = $cacheClass;
    }

    /**
     * @param string $selectedVisibility
     * @param VisibilityInterface $productVisibility|null
     * @return array
     */
    protected function resolveStaticValues($selectedVisibility, VisibilityInterface $productVisibility = null)
    {
        $updateData = [
            'sourceProductVisibility' => $productVisibility,
            'source' => BaseProductVisibilityResolved::SOURCE_STATIC,
            'category' => null,
        ];

        if ($selectedVisibility === VisibilityInterface::VISIBLE) {
            $updateData['visibility'] = BaseVisibilityResolved::VISIBILITY_VISIBLE;
        } elseif ($selectedVisibility === VisibilityInterface::HIDDEN) {
            $updateData['visibility'] = BaseVisibilityResolved::VISIBILITY_HIDDEN;
        }

        return $updateData;
    }

    /**
     * @param EntityRepository|BasicOperationRepositoryTrait $repository
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
     * @param boolean $isVisible
     * @return integer
     */
    protected function convertVisibility($isVisible)
    {
        return $isVisible ? BaseVisibilityResolved::VISIBILITY_VISIBLE
            : BaseVisibilityResolved::VISIBILITY_HIDDEN;
    }

    /**
     * @param string $visibility
     * @return int
     */
    protected function convertStaticVisibility($visibility)
    {
        return $visibility === VisibilityInterface::VISIBLE
            ? BaseVisibilityResolved::VISIBILITY_VISIBLE
            : BaseVisibilityResolved::VISIBILITY_HIDDEN;
    }

    /**
     * @param object $entity
     * @return object|null
     */
    protected function refreshEntity($entity)
    {
        $entityClass = ClassUtils::getClass($entity);
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getManagerForClass($entityClass);

        if ($entityManager->getUnitOfWork()->getEntityState($entity) !== UnitOfWork::STATE_MANAGED) {
            $identifier = $entityManager->getClassMetadata($entityClass)->getIdentifierValues($entity);
            if ($identifier) {
                $entity = $entityManager->getRepository($entityClass)->find($identifier);
            } else {
                $entity = null;
            }
        } else {
            $entityManager->refresh($entity);
        }

        return $entity;
    }

    /**
     * Use category ID as array index
     *
     * @param array $visibilities
     * @param string $fieldName
     * @return array
     */
    protected function indexVisibilities(array $visibilities, $fieldName)
    {
        $indexedVisibilities = [];
        foreach ($visibilities as $visibility) {
            $index = $visibility[$fieldName];
            $indexedVisibilities[$index] = $visibility;
        }

        return $indexedVisibilities;
    }

    /**
     * @param Product $product
     * @param Website $website
     * @param bool $schedule
     */
    protected function triggerProductReindexation(
        Product $product,
        Website $website = null,
        bool $schedule = false
    ): void {
        $this->productReindexManager->reindexProduct(
            $product,
            $website ? $website->getId() : null,
            $schedule
        );
    }

    /**
     * @param array|Category[]|int[] $categories
     */
    protected function triggerCategoriesReindexation(array $categories)
    {
        $this->indexScheduler->scheduleProductsReindex($categories, null);
    }

    /**
     * @param EntityRepository $repository
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;
    }
}
