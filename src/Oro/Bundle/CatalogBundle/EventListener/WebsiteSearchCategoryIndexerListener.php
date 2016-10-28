<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

class WebsiteSearchCategoryIndexerListener
{
    use ContextTrait;

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
        /** @var Product[] $products */
        $products = $event->getEntities();

        $context = $event->getContext();

        $websiteId = $this->getContextCurrentWebsiteId($context);

        $localizations = $this->websiteLocalizationProvider->getLocalizationsByWebsiteId($websiteId);

        $categoryMap = $this->getRepository()->getCategoryMapByProducts($products, $localizations);

        foreach ($products as $product) {
            /** @var Category $category */
            $category = &$categoryMap[$product->getId()];
            if (!empty($category)) {
                // Non localized fields
                $event->addField($product->getId(), 'category_id', $category->getId());
                $event->addField($product->getId(), 'category_path', $category->getMaterializedPath());

                $placeholders = [LocalizationIdPlaceholder::NAME => Localization::DEFAULT_LOCALIZATION];
                $event->addPlaceholderField(
                    $product->getId(),
                    IndexDataProvider::ALL_TEXT_L10N_FIELD,
                    (string)$category->getDefaultTitle(),
                    $placeholders
                );
                $event->addPlaceholderField(
                    $product->getId(),
                    IndexDataProvider::ALL_TEXT_L10N_FIELD,
                    (string)$category->getDefaultLongDescription(),
                    $placeholders
                );

                $event->addPlaceholderField(
                    $product->getId(),
                    IndexDataProvider::ALL_TEXT_L10N_FIELD,
                    (string)$category->getDefaultShortDescription(),
                    $placeholders
                );

                // Localized fields
                foreach ($localizations as $localization) {
                    $placeholders = [LocalizationIdPlaceholder::NAME => $localization->getId()];

                    $event->addPlaceholderField(
                        $product->getId(),
                        IndexDataProvider::ALL_TEXT_L10N_FIELD,
                        (string)$category->getTitle($localization),
                        $placeholders
                    );

                    $event->addPlaceholderField(
                        $product->getId(),
                        IndexDataProvider::ALL_TEXT_L10N_FIELD,
                        (string)$category->getLongDescription($localization),
                        $placeholders
                    );

                    $event->addPlaceholderField(
                        $product->getId(),
                        IndexDataProvider::ALL_TEXT_L10N_FIELD,
                        (string)$category->getShortDescription($localization),
                        $placeholders
                    );
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
