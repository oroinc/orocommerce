<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

/**
 * Adds following category SEO fields to Product documents at search index
 * - meta title (all_text_LOCALIZATION_ID)
 * - meta description (all_text_LOCALIZATION_ID)
 * - meta keywords (all_text_LOCALIZATION_ID)
 */
class ProductSearchIndexListener
{
    private DoctrineHelper $doctrineHelper;
    private AbstractWebsiteLocalizationProvider $websiteLocalizationProvider;
    private WebsiteContextManager $websiteContextManager;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        AbstractWebsiteLocalizationProvider $websiteLocalizationProvider,
        WebsiteContextManager $websiteContextManager
    ) {
        $this->doctrineHelper              = $doctrineHelper;
        $this->websiteLocalizationProvider = $websiteLocalizationProvider;
        $this->websiteContextManager       = $websiteContextManager;
    }

    /**
     * @param Product|Category $entity
     * @param Localization $localization
     * @return array
     */
    protected function getMetaFieldsForEntity($entity, $localization)
    {
        return [
            $entity->getMetaTitle($localization),
            $entity->getMetaDescription($localization),
            $entity->getMetaKeyword($localization)
        ];
    }

    /**
     * @param IndexEntityEvent $event
     * @param int $productId
     * @param string $metaField
     * @param int $localizationId
     */
    protected function addPlaceholderToEvent($event, $productId, $metaField, $localizationId)
    {
        $metaField = $this->cleanUpString($metaField);
        $event->addPlaceholderField(
            $productId,
            IndexDataProvider::ALL_TEXT_L10N_FIELD,
            $metaField,
            [LocalizationIdPlaceholder::NAME => $localizationId],
            true
        );
    }

    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        $websiteId = $this->websiteContextManager->getWebsiteId($event->getContext());
        if (!$websiteId) {
            $event->stopPropagation();

            return;
        }

        /** @var Product[] $products */
        $products = $event->getEntities();
        $localizations = $this->websiteLocalizationProvider->getLocalizationsByWebsiteId($websiteId);
        $categoryMap = $this->getRepository()->getCategoryMapByProducts($products);
        foreach ($products as $product) {
            $category = $categoryMap[$product->getId()] ?? null;
            if (null === $category) {
                continue;
            }
            foreach ($localizations as $localization) {
                foreach ($this->getMetaFieldsForEntity($category, $localization) as $metaField) {
                    if (!$metaField) {
                        continue;
                    }
                    $this->addPlaceholderToEvent($event, $product->getId(), $metaField, $localization->getId());
                }
            }
        }
    }

    /**
     * Cleans up a unicode string from control characters.
     *
     * @param string $string
     * @return string
     */
    private function cleanUpString($string)
    {
        return preg_replace('/[[:cntrl:]]/', '', $string);
    }

    /**
     * @return CategoryRepository
     */
    protected function getRepository()
    {
        return $this->doctrineHelper->getEntityRepository(Category::class);
    }
}
