<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Helper\FieldHelper;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\ChainReplacePlaceholder;

class WebsiteSearchProductIndexerListener
{
    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @var ChainReplacePlaceholder
     */
    private $chainReplacePlaceholder;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param LocalizationHelper $localizationHelper
     * @param ChainReplacePlaceholder $chainReplacePlaceholder
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        LocalizationHelper $localizationHelper,
        ChainReplacePlaceholder $chainReplacePlaceholder
    ) {
        $this->productRepository = $doctrineHelper->getEntityRepositoryForClass(Product::class);
        $this->localizationHelper = $localizationHelper;
        $this->chainReplacePlaceholder = $chainReplacePlaceholder;
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
                    'description' => $product->getDescription($localization),
                    'short_desc' => $product->getShortDescription($localization)
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
}
