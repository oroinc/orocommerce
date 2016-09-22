<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

class WebsiteSearchProductIndexerListener
{
    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        LocalizationHelper $localizationHelper
    ) {
        $this->productRepository = $doctrineHelper->getEntityRepositoryForClass(Product::class);
        $this->localizationHelper = $localizationHelper;
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
            $event->addField($product->getId(), 'sku', $product->getSku());
            $event->addField($product->getId(), 'status', $product->getStatus());
            $event->addField($product->getId(), 'inventory_status', $product->getInventoryStatus()->getId());

            // Localized fields
            foreach ($localizations as $localization) {
                $localizedFields = [
                    'title' => $product->getName($localization),
                    'description' => $product->getDescription($localization),
                    'short_desc' => $product->getShortDescription($localization)
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
            $event->setAllTextFieldPlaceholder($product->getId(), LocalizationIdPlaceholder::NAME);
        }
    }
}
