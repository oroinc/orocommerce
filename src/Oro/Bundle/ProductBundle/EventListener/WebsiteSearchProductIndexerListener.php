<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

class WebsiteSearchProductIndexerListener
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
        }
    }
}
