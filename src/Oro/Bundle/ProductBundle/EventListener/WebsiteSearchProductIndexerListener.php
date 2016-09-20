<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

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
     * @var ProductRepository
     */
    private $productRepository;

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
     * @return ProductRepository
     */
    protected function getProductRepository()
    {
        if (!$this->productRepository) {
            $this->productRepository = $this->doctrineHelper->getEntityRepositoryForClass(Product::class);
        }

        return $this->productRepository;
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

        $products = $this->getProductRepository()->getProductsByIds($event->getEntityIds());

        $context = $event->getContext();

        $websiteId = (array_key_exists(AbstractIndexer::CONTEXT_WEBSITE_ID_KEY, $context))
            ? $context[AbstractIndexer::CONTEXT_WEBSITE_ID_KEY]
            : null;

        $localizations = $this->websiteLocalizationProvider->getLocalizationsByWebsiteId($websiteId);

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
