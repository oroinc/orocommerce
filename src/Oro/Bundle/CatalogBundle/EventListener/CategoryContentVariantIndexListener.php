<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Component\DoctrineUtils\ORM\ChangedEntityGeneratorTrait;
use Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface;
use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\ContentNodeFieldsChangesAwareInterface;
use Oro\Component\DoctrineUtils\ORM\FieldUpdatesChecker;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;
use Oro\Bundle\CatalogBundle\Entity\Category;

class CategoryContentVariantIndexListener implements ContentNodeFieldsChangesAwareInterface
{
    use ChangedEntityGeneratorTrait;

    /** @var ProductIndexScheduler */
    private $indexScheduler;

    /** @var PropertyAccessorInterface */
    private $accessor;

    /** @var WebCatalogUsageProviderInterface */
    private $webCatalogUsageProvider;

    /** @var FieldUpdatesChecker */
    private $fieldUpdatesChecker;

    /**
     * List of fields of ContentNode that this class will listen to changes.
     * If any of fields have any changes, product reindexation will be triggered.
     *
     * @var array
     */
    protected $fieldsChangesListenTo = ['titles'];

    /**
     * @param ProductIndexScheduler $indexScheduler
     * @param PropertyAccessorInterface $accessor
     * @param FieldUpdatesChecker       $fieldUpdatesChecker
     * @param WebCatalogUsageProviderInterface|null $webCatalogUsageProvider
     */
    public function __construct(
        ProductIndexScheduler $indexScheduler,
        PropertyAccessorInterface $accessor,
        FieldUpdatesChecker $fieldUpdatesChecker,
        WebCatalogUsageProviderInterface $webCatalogUsageProvider = null
    ) {
        $this->indexScheduler = $indexScheduler;
        $this->accessor = $accessor;
        $this->fieldUpdatesChecker = $fieldUpdatesChecker;
        $this->webCatalogUsageProvider = $webCatalogUsageProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function addField($fieldName)
    {
        if (!in_array($fieldName, $this->fieldsChangesListenTo, true)) {
            $this->fieldsChangesListenTo[] = $fieldName;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        return $this->fieldsChangesListenTo;
    }

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $unitOfWork = $event->getEntityManager()->getUnitOfWork();
        $categories = [];
        $websiteIds = [];

        foreach ($this->getUpdatedEntities($unitOfWork) as $entity) {
            $isAnyFieldChanged = false;

            if (!$entity instanceof ContentNodeInterface) {
                continue;
            }

            foreach ($this->getFields() as $fieldName) {
                if ($this->fieldUpdatesChecker->isRelationFieldChanged($entity, $fieldName)) {
                    $isAnyFieldChanged = true;
                    break;
                }
            }

            // if any of configurable field of ContentNode has changed - reindex all products of related categories
            if ($isAnyFieldChanged) {
                $this->collectCategories($entity->getContentVariants(), $categories);
                $this->collectWebsiteIds($entity->getContentVariants(), $websiteIds);
            }
        }

        foreach ($this->getChangedEntities($unitOfWork) as $entity) {
            $this->collectCategories([$entity], $categories, $unitOfWork);
            $this->collectWebsiteIds([$entity], $websiteIds);
        }

        if ($categories) {
            $this->scheduleProductsReindex($categories, $websiteIds);
        }
    }

    /**
     * @param array|Collection $entities
     * @param Category[] &$categories
     * @param UnitOfWork $unitOfWork
     */
    private function collectCategories($entities, array &$categories, $unitOfWork = null)
    {
        foreach ($entities as $entity) {
            if ($entity instanceof ContentVariantInterface
                && $entity->getType() === CategoryPageContentVariantType::TYPE
            ) {
                /** @var Category $category */
                $category = $this->accessor->getValue($entity, 'category_page_category');
                if ($category) {
                    $this->addCategory($categories, $category);
                }

                if ($unitOfWork) {
                    $changeSet = $unitOfWork->getEntityChangeSet($entity);
                    if (!array_key_exists('category_page_category', $changeSet)) {
                        continue;
                    }
                    if (!empty($changeSet['category_page_category'][0])) {
                        $this->addCategory($categories, $changeSet['category_page_category'][0]);
                    }
                    if (!empty($changeSet['category_page_category'][1])) {
                        $this->addCategory($categories, $changeSet['category_page_category'][1]);
                    }
                }
            }
        }
    }

    /**
     * @param array|Collection $entities
     * @param array|null &$websitesId
     */
    private function collectWebsiteIds($entities, &$websitesId)
    {
        if ($this->webCatalogUsageProvider === null) {
            return;
        }

        $assignedWebCatalogs = $this->webCatalogUsageProvider->getAssignedWebCatalogs();
        foreach ($entities as $entity) {
            if (!$entity instanceof ContentVariantInterface
                || $entity->getType() !== CategoryPageContentVariantType::TYPE) {
                continue;
            }
            $webCatalogId = $entity->getNode()->getWebCatalog()->getId();
            if (count($assignedWebCatalogs) === 1 && array_key_exists('0', $assignedWebCatalogs)) {
                $websitesId = [];
                return;
            }
            $relatedWebsiteIds = array_filter(
                $assignedWebCatalogs,
                function ($relatedWebsiteWebCatalogId) use ($webCatalogId) {
                    return $webCatalogId == $relatedWebsiteWebCatalogId;
                }
            );
            if (!empty($relatedWebsiteIds)) {
                $websitesId = array_unique(array_merge($websitesId, array_keys($relatedWebsiteIds)));
            }
        }
    }

    /**
     * @param Category[] &$categories
     * @param Category $category
     */
    private function addCategory(array &$categories, Category $category)
    {
        $categoryId = $category->getId();
        if ($categoryId && !array_key_exists($categoryId, $categories)) {
            $categories[$categoryId] = $category;
        }
    }

    /**
     * @param array $categories
     * @param array $websiteIds
     */
    private function scheduleProductsReindex(array $categories, array $websiteIds = [])
    {
        if (count($websiteIds) === 0) {
            $this->indexScheduler->scheduleProductsReindex($categories);

            return;
        }

        foreach ($websiteIds as $websiteId) {
            $this->indexScheduler->scheduleProductsReindex($categories, $websiteId);
        }
    }
}
