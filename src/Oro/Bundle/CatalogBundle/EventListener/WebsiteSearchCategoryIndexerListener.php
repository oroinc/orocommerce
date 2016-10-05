<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

class WebsiteSearchCategoryIndexerListener
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var WebsiteLocalizationProvider
     */
    private $websiteLocalizationProvider;

    /**
     * @var CategoryRepository
     */
    private $repository;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param AbstractWebsiteLocalizationProvider $websiteLocalizationProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        AbstractWebsiteLocalizationProvider $websiteLocalizationProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->websiteLocalizationProvider = $websiteLocalizationProvider;
    }

    /**
     * @param IndexEntityEvent $event
     */
    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        $entityClass = $event->getEntityClass();

        if (!is_a($entityClass, Product::class, true)) {
            return;
        }

        /** @var Product[] $products */
        $products = $event->getEntities();

        $context = $event->getContext();

        $websiteId = (array_key_exists(AbstractIndexer::CONTEXT_WEBSITE_ID_KEY, $context))
            ? $context[AbstractIndexer::CONTEXT_WEBSITE_ID_KEY]
            : null;

        $localizations = $this->websiteLocalizationProvider->getLocalizationsByWebsiteId($websiteId);
        $categoryMap = $this->getRepository()->getCategoryMapByProducts($products, $localizations);

        foreach ($products as $product) {
            /** @var Category $category */
            $category = &$categoryMap[$product->getId()];
            if (!empty($category)) {
                // Non localized fields
                $event->addField($product->getId(), 'category_id', $category->getId());
                $event->addField($product->getId(), 'category_path', $category->getMaterializedPath());

                // Localized fields
                foreach ($localizations as $localization) {
                    $localizedFields = [
                        'category_title' => $category->getTitle($localization),
                        'category_description' => $category->getLongDescription($localization),
                        'category_short_desc' => $category->getShortDescription($localization)
                    ];

                    foreach ($localizedFields as $fieldName => $fieldValue) {
                        $event->addPlaceholderField(
                            $product->getId(),
                            $fieldName,
                            $fieldValue,
                            [LocalizationIdPlaceholder::NAME => $localization->getId()]
                        );
                    }
                }
            }
        }
    }

    /**
     * @return CategoryRepository
     */
    protected function getRepository()
    {
        if (!$this->repository) {
            $this->repository = $this->doctrineHelper->getEntityRepository(Category::class);
        }

        return $this->repository;
    }
}
