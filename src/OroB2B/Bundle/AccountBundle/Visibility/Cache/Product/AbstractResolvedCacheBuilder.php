<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\BasicOperationRepositoryTrait;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;
use OroB2B\Bundle\AccountBundle\Visibility\Resolver\CategoryVisibilityResolver;
use OroB2B\Bundle\AccountBundle\Visibility\Resolver\CategoryVisibilityResolverInterface;

abstract class AbstractResolvedCacheBuilder implements CacheBuilderInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var CategoryVisibilityResolverInterface
     */
    protected $categoryVisibilityResolver;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertFromSelectQueryExecutor;

    /**
     * @var string
     */
    protected $cacheClass;

    /**
     * @var int
     */
    protected $visibilityFromConfig;

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
     * @return int
     */
    protected function getVisibilityFromConfig()
    {
        if (!$this->visibilityFromConfig) {
            $visibilityFromConfig = $this->configManager->get('oro_b2b_account.product_visibility');
            $this->visibilityFromConfig
                = $this->convertVisibility($visibilityFromConfig === VisibilityInterface::VISIBLE);
        }

        return $this->visibilityFromConfig;
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
     * @return array
     */
    protected function indexVisibilities(array $visibilities)
    {
        $indexedVisibilities = [];
        foreach ($visibilities as $visibility) {
            $categoryId = $visibility['category_id'];
            $indexedVisibilities[$categoryId] = $visibility;
        }

        return $indexedVisibilities;
    }
}
