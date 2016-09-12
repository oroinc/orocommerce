<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

class WebsiteSearchProductIndexerListener
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper, LocalizationHelper $localizationHelper)
    {
        $this->productRepository = $doctrineHelper->getEntityRepositoryForClass(Product::class);
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param IndexEntityEvent $event
     */
    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        $entityClass = $event->getEntityClass();
        $productIds = $event->getEntityIds();

        if ($entityClass !== Product::class) {
            return;
        } elseif (!$productIds) {
            return;
        }

        $products = $this->productRepository->getProductsByIds($productIds);

        $localizations = $this->localizationHelper->getLocalizations();

        foreach ($products as $product) {
            // Non localized fields
            $event->addField(
                $product->getId(),
                Query::TYPE_TEXT,
                'sku',
                $product->getSku()
            );
            $event->addField(
                $product->getId(),
                Query::TYPE_TEXT,
                'status',
                $product->getStatus()
            );
            $event->addField(
                $product->getId(),
                Query::TYPE_TEXT,
                'inventory_status',
                $product->getInventoryStatus()->getId()
            );

            // Localized fields
            foreach ($localizations as $localization) {
                $localizedFields = [
                    'title' => $product->getName($localization),
                    'description' => $this->stripTagsAndSpaces($product->getDescription($localization)),
                    'short_desc' => $this->stripTagsAndSpaces($product->getShortDescription($localization)),
                ];

                foreach ($localizedFields as $fieldName => $fieldValue) {
                    $event->addField(
                        $product->getId(),
                        Query::TYPE_TEXT,
                        sprintf('%s_%s', $fieldName, $localization->getId()),
                        $fieldValue
                    );
                }

                // All text field
                $event->addField(
                    $product->getId(),
                    Query::TYPE_TEXT,
                    sprintf('all_text_%s', $localization->getId()),
                    implode(' ', $localizedFields)
                );
            }
        }
    }

    /**
     * @param string $text
     * @return string
     */
    private function stripTagsAndSpaces($text)
    {
        $stripTagsWithExcessiveSpaces = html_entity_decode(
            strip_tags(
                str_replace('>', '> ', $text)
            )
        );

        return trim(
            preg_replace('/\s+/u', ' ', $stripTagsWithExcessiveSpaces)
        );
    }
}
