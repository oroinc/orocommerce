<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\PropertyAccess\PropertyAccessor;

class ProductSearchIndexListener
{
    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @param DoctrineHelper     $doctrineHelper
     * @param LocalizationHelper $localizationHelper
     * @param PropertyAccessor   $propertyAccessor
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        LocalizationHelper $localizationHelper,
        PropertyAccessor $propertyAccessor
    ) {
        $this->productRepository  = $doctrineHelper->getEntityRepositoryForClass(Product::class);
        $this->localizationHelper = $localizationHelper;
        $this->propertyAccessor   = $propertyAccessor;
    }

    /**
     * @param IndexEntityEvent $event
     */
    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        $entityClass = $event->getEntityClass();

        if ($entityClass !== Product::class) {
            return;
        }

        $products = $this->productRepository->getProductsByIds($event->getEntityIds());

        $localizations = $this->localizationHelper->getLocalizations();

        foreach ($products as $product) {
            // Localized fields
            foreach ($localizations as $localization) {
                $metaStrings = $this->getMetaStringsFromProduct($product, $localization);
                // All text field
                $event->appendField(
                    $product->getId(),
                    Query::TYPE_TEXT,
                    sprintf('all_text_%s', $localization->getId()),
                    ' ' . $metaStrings
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
}
