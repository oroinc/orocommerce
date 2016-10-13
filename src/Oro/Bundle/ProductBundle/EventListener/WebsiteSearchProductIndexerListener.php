<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
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
            $placeholders = [LocalizationIdPlaceholder::NAME => Localization::DEFAULT_LOCALIZATION];
            $event->addPlaceholderField($product->getId(), 'title', (string)$product->getDefaultName(), $placeholders);

            $event->addPlaceholderField(
                $product->getId(),
                'description',
                (string)$product->getDefaultDescription(),
                $placeholders
            );

            $event->addPlaceholderField(
                $product->getId(),
                'short_desc',
                (string)$product->getDefaultShortDescription(),
                $placeholders
            );

            foreach ($localizations as $localization) {
                $localizationId = $localization->getId();
                $placeholders = [LocalizationIdPlaceholder::NAME => $localizationId];
                $event->addPlaceholderField(
                    $product->getId(),
                    'title',
                    (string)$product->getName($localization),
                    $placeholders
                );

                $event->addPlaceholderField(
                    $product->getId(),
                    'description',
                    (string)$product->getDescription($localization),
                    $placeholders
                );

                $event->addPlaceholderField(
                    $product->getId(),
                    'short_desc',
                    (string)$product->getShortDescription($localization),
                    $placeholders
                );
            }
        }
    }
}
