<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
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
    use ContextTrait;

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

    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        if (!$this->hasContextFieldGroup($event->getContext(), 'main')) {
            return;
        }

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
                $metaField = (string)$category->getMetaTitle($localization);
                if ($metaField) {
                    $this->addPlaceholderToEvent($event, $product, $metaField, $localization);
                }
                $metaField = (string)$category->getMetaDescription($localization);
                if ($metaField) {
                    $this->addPlaceholderToEvent($event, $product, $metaField, $localization);
                }
                $metaField = (string)$category->getMetaKeyword($localization);
                if ($metaField) {
                    $this->addPlaceholderToEvent($event, $product, $metaField, $localization);
                }
            }
        }
    }

    private function addPlaceholderToEvent(
        IndexEntityEvent $event,
        Product $product,
        string $metaField,
        Localization $localization
    ): void {
        $event->addPlaceholderField(
            $product->getId(),
            IndexDataProvider::ALL_TEXT_L10N_FIELD,
            $this->cleanUpString($metaField),
            [LocalizationIdPlaceholder::NAME => $localization->getId()],
            true
        );
    }

    /**
     * Cleans up a unicode string from control characters.
     */
    private function cleanUpString(string $string): string
    {
        return preg_replace('/[[:cntrl:]]/', '', (string)$string);
    }

    private function getRepository(): CategoryRepository
    {
        return $this->doctrineHelper->getEntityRepository(Category::class);
    }
}
