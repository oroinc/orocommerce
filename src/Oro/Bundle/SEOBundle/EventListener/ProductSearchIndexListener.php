<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductSearchIndexListener
{
    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @var AbstractWebsiteLocalizationProvider
     */
    private $websiteLocalizationProvider;

    /**
     * @param AbstractWebsiteLocalizationProvider $websiteLocalizationProvider
     * @param PropertyAccessor                    $propertyAccessor
     */
    public function __construct(
        AbstractWebsiteLocalizationProvider $websiteLocalizationProvider,
        PropertyAccessor $propertyAccessor
    ) {
        $this->websiteLocalizationProvider = $websiteLocalizationProvider;
        $this->propertyAccessor            = $propertyAccessor;
    }

    /**
     * @param IndexEntityEvent $event
     */
    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        /** @var Product[] $products */
        $products = $event->getEntities();

        $context = $event->getContext();

        $websiteId = (array_key_exists(AbstractIndexer::CONTEXT_WEBSITE_ID_KEY, $context))
            ? $context[AbstractIndexer::CONTEXT_WEBSITE_ID_KEY]
            : null;

        $localizations = $this->websiteLocalizationProvider->getLocalizationsByWebsiteId($websiteId);

        foreach ($products as $product) {
            // Localized fields
            foreach ($localizations as $localization) {
                $metaStrings = $this->getMetaStringsFromProduct($product, $localization);
                $this->appendToPlaceholderField(
                    $event,
                    $product->getId(),
                    $localization,
                    $metaStrings
                );
            }
        }
    }

    /**
     * @param Product      $product
     * @param Localization $localization
     * @return string
     */
    private function getMetaStringsFromProduct(Product $product, Localization $localization)
    {
        $metaTitle       = $product->getMetaTitle($localization);
        $metaDescription = $product->getMetaDescription($localization);
        $metaKeyword     = $product->getMetaKeyword($localization);

        $string = $metaTitle . ' ' . $metaDescription . ' ' . $metaKeyword;

        return $this->cleanUpString($string);
    }

    /**
     * Cleans up a unicode string from control characters.
     *
     * @param $string
     * @return string
     */
    private function cleanUpString($string)
    {
        $clean = preg_replace('/[[:cntrl:]]/', '', $string);

        return $clean;
    }

    /**
     * @param IndexEntityEvent $event
     * @param integer          $entityId
     * @param Localization     $localization
     * @param string           $metaStrings
     * @param string           $fieldName
     */
    private function appendToPlaceholderField(
        IndexEntityEvent $event,
        $entityId,
        Localization $localization,
        $metaStrings,
        $fieldName = 'all_text'
    ) {
        $placeholderKey   = LocalizationIdPlaceholder::NAME;
        $placeholderValue = $localization->getId();

        $event->appendToPlaceholderField(
            $entityId,
            $fieldName,
            $metaStrings,
            $placeholderKey,
            $placeholderValue
        );
    }
}
