<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

class WebsiteSearchProductIndexerListener
{
    /**
     * @var WebsiteContextManager
     */
    private $websiteContextManger;

    /**
     * @var WebsiteLocalizationProvider
     */
    private $websiteLocalizationProvider;

    /**
     * @var RegistryInterface
     */
    private $registry;

    /**
     * @var AttachmentManager
     */
    private $attachmentManager;

    /**
     * @param AbstractWebsiteLocalizationProvider $websiteLocalizationProvider
     * @param WebsiteContextManager               $websiteContextManager
     * @param RegistryInterface                   $registry
     * @param AttachmentManager                   $attachmentManager
     */
    public function __construct(
        AbstractWebsiteLocalizationProvider $websiteLocalizationProvider,
        WebsiteContextManager $websiteContextManager,
        RegistryInterface $registry,
        AttachmentManager $attachmentManager
    ) {
        $this->websiteLocalizationProvider = $websiteLocalizationProvider;
        $this->websiteContextManger = $websiteContextManager;
        $this->registry = $registry;
        $this->attachmentManager = $attachmentManager;
    }

    /**
     * @param IndexEntityEvent $event
     */
    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        $websiteId = $this->websiteContextManger->getWebsiteId($event->getContext());
        if (!$websiteId) {
            $event->stopPropagation();

            return;
        }

        /** @var Product[] $products */
        $products = $event->getEntities();

        $productIds = array_map(
            function (Product $product) {
                return $product->getId();
            },
            $products
        );

        $localizations = $this->websiteLocalizationProvider->getLocalizationsByWebsiteId($websiteId);
        $productImages = $this->getProductRepository()->getListingImagesFilesByProductIds($productIds);
        $productUnits = $this->getProductUnitRepository()->getProductsUnits($productIds);

        foreach ($products as $product) {
            // Non localized fields
            $event->addField($product->getId(), 'product_id', $product->getId());
            $event->addField($product->getId(), 'sku', $product->getSku(), true);
            $event->addField($product->getId(), 'sku_uppercase', strtoupper($product->getSku()), true);
            $event->addField($product->getId(), 'status', $product->getStatus());
            $event->addField($product->getId(), 'inventory_status', $product->getInventoryStatus()->getId());
            $event->addField($product->getId(), 'type', $product->getType());
            $event->addField($product->getId(), 'new_arrival', (int)$product->isNewArrival());

            if (isset($productImages[$product->getId()])) {
                /** @var File $entity */
                $entity = $productImages[$product->getId()];
                $largeImageUrl = $this->attachmentManager->getFilteredImageUrl(
                    $entity,
                    FrontendProductDatagridListener::PRODUCT_IMAGE_FILTER_LARGE
                );
                $mediumImageUrl = $this->attachmentManager->getFilteredImageUrl(
                    $entity,
                    FrontendProductDatagridListener::PRODUCT_IMAGE_FILTER_MEDIUM
                );
                $event->addField(
                    $product->getId(),
                    'image_' . FrontendProductDatagridListener::PRODUCT_IMAGE_FILTER_LARGE,
                    $largeImageUrl
                );
                $event->addField(
                    $product->getId(),
                    'image_' . FrontendProductDatagridListener::PRODUCT_IMAGE_FILTER_MEDIUM,
                    $mediumImageUrl
                );
            }

            if (array_key_exists($product->getId(), $productUnits)) {
                $units = [];
                foreach ($productUnits[$product->getId()] as $unitCode) {
                    $units[] = $unitCode;
                }
                $event->addField(
                    $product->getId(),
                    'product_units',
                    implode('|', $units)
                );
            }

            foreach ($localizations as $localization) {
                $localizationId = $localization->getId();
                $placeholders = [LocalizationIdPlaceholder::NAME => $localizationId];
                $event->addPlaceholderField(
                    $product->getId(),
                    'name_LOCALIZATION_ID',
                    (string)$product->getName($localization),
                    $placeholders,
                    true
                );

                $event->addPlaceholderField(
                    $product->getId(),
                    'short_description_LOCALIZATION_ID',
                    (string)$product->getShortDescription($localization),
                    $placeholders,
                    true
                );

                $event->addPlaceholderField(
                    $product->getId(),
                    IndexDataProvider::ALL_TEXT_L10N_FIELD,
                    (string)$product->getDescription($localization),
                    $placeholders,
                    true
                );
            }
        }
    }

    /**
     * @return ProductRepository
     */
    protected function getProductRepository()
    {
        return $this->registry
            ->getManagerForClass('OroProductBundle:Product')
            ->getRepository('OroProductBundle:Product');
    }

    /**
     * @return ProductUnitRepository
     */
    protected function getProductUnitRepository()
    {
        return $this->registry
            ->getManagerForClass('OroProductBundle:ProductUnit')
            ->getRepository('OroProductBundle:ProductUnit');
    }
}
