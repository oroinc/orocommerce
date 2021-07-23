<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Search\ProductIndexDataProviderInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

/**
 * Add product related data to search index
 * Main data added from product attributes and some data added manually inside listener
 */
class WebsiteSearchProductIndexerListener
{
    /** @var WebsiteContextManager */
    private $websiteContextManager;

    /** @var WebsiteLocalizationProvider */
    private $websiteLocalizationProvider;

    /** @var ManagerRegistry */
    private $registry;

    /** @var AttachmentManager */
    private $attachmentManager;

    /** @var AttributeManager */
    private $attributeManager;

    /** @var ProductIndexDataProviderInterface */
    private $dataProvider;

    public function __construct(
        AbstractWebsiteLocalizationProvider $websiteLocalizationProvider,
        WebsiteContextManager $websiteContextManager,
        ManagerRegistry $registry,
        AttachmentManager $attachmentManager,
        AttributeManager $attributeManager,
        ProductIndexDataProviderInterface $dataProvider
    ) {
        $this->websiteLocalizationProvider = $websiteLocalizationProvider;
        $this->websiteContextManager = $websiteContextManager;
        $this->registry = $registry;
        $this->attachmentManager = $attachmentManager;
        $this->attributeManager = $attributeManager;
        $this->dataProvider = $dataProvider;
    }

    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        $website = $this->getWebsite($event);
        if (!$website) {
            $event->stopPropagation();

            return;
        }

        /** @var Product[] $products */
        $products = $event->getEntities();

        $productIds = array_map(
            static function (Product $product) {
                return $product->getId();
            },
            $products
        );

        $localizations = $this->websiteLocalizationProvider->getLocalizationsByWebsiteId($website->getId());
        $productImages = $this->getProductRepository()->getListingImagesFilesByProductIds($productIds);
        $productUnits = $this->getProductUnitRepository()->getProductsUnits($productIds);
        $primaryUnits = $this->getProductUnitRepository()->getPrimaryProductsUnits($productIds);
        $attributes = $this->attributeManager
            ->getActiveAttributesByClassForOrganization($event->getEntityClass(), $website->getOrganization());
        $attributeFamilies = $this->getAttributeFamilyRepository()
            ->getFamilyIdsForAttributesByOrganization($attributes, $website->getOrganization());

        foreach ($products as $product) {
            $productId = $product->getId();

            foreach ($attributes as $attribute) {
                if (!$this->isAllowedToIndex($attribute, $product, $attributeFamilies)) {
                    continue;
                }

                $data = $this->dataProvider->getIndexData($product, $attribute, $localizations);
                $this->processIndexData($event, $productId, $data);
            }

            $event->addField($product->getId(), 'product_id', $product->getId());
            $event->addField($product->getId(), 'sku_uppercase', mb_strtoupper($product->getSku()), true);
            $event->addField($product->getId(), 'status', $product->getStatus());
            $event->addField($product->getId(), 'type', $product->getType());
            $event->addField(
                $product->getId(),
                'inventory_status',
                $product->getInventoryStatus() ? $product->getInventoryStatus()->getId() : ''
            );
            $event->addField($product->getId(), 'is_variant', (int)$product->isVariant());

            if ($product->getAttributeFamily() instanceof AttributeFamily) {
                $event->addField(
                    $product->getId(),
                    'attribute_family_id',
                    $product->getAttributeFamily()->getId()
                );
            }

            $this->processImages($event, $productImages, $product->getId());

            if (isset($primaryUnits[$product->getId()])) {
                $event->addField(
                    $product->getId(),
                    'primary_unit',
                    $primaryUnits[$product->getId()]
                );
            }

            if (array_key_exists($product->getId(), $productUnits)) {
                $units = serialize($productUnits[$product->getId()]);
                $event->addField(
                    $product->getId(),
                    'product_units',
                    $units
                );
            }
        }
    }

    private function getWebsite(IndexEntityEvent $event): ?Website
    {
        $websiteId = $this->websiteContextManager->getWebsiteId($event->getContext());
        if ($websiteId) {
            return $this->registry->getManagerForClass(Website::class)->find(Website::class, $websiteId);
        }

        return null;
    }

    private function processIndexData(IndexEntityEvent $event, int $productId, iterable $data): void
    {
        foreach ($data as $content) {
            $value = $this->cleanUpStrings($content->getValue());
            if (null === $value) {
                continue;
            }

            if ($content->isLocalized()) {
                $event->addPlaceholderField(
                    $productId,
                    $content->getFieldName(),
                    $value,
                    $content->getPlaceholders(),
                    $content->isSearchable()
                );
            } else {
                $event->addField($productId, $content->getFieldName(), $value, $content->isSearchable());
            }
        }
    }

    /**
     * @param FieldConfigModel $attribute
     * @param Product $product
     * @param array $attributeFamilies
     * @return bool
     */
    private function isAllowedToIndex(FieldConfigModel $attribute, Product $product, array $attributeFamilies)
    {
        if ($this->attributeManager->isSystem($attribute)) {
            return true;
        }

        if (!isset($attributeFamilies[$attribute->getId()])) {
            return false;
        }

        $attributeFamily = $product->getAttributeFamily();

        return !$attributeFamily || in_array($attributeFamily->getId(), $attributeFamilies[$attribute->getId()], true);
    }

    /**
     * @param IndexEntityEvent $event
     * @param array $productImages
     * @param int $productId
     */
    private function processImages(IndexEntityEvent $event, array $productImages, $productId)
    {
        if (isset($productImages[$productId])) {
            /** @var File $entity */
            $entity = $productImages[$productId];
            $largeImageUrl = $this->attachmentManager->getFilteredImageUrl(
                $entity,
                FrontendProductDatagridListener::PRODUCT_IMAGE_FILTER_LARGE
            );
            $mediumImageUrl = $this->attachmentManager->getFilteredImageUrl(
                $entity,
                FrontendProductDatagridListener::PRODUCT_IMAGE_FILTER_MEDIUM
            );
            $smallImageUrl = $this->attachmentManager->getFilteredImageUrl(
                $entity,
                FrontendProductDatagridListener::PRODUCT_IMAGE_FILTER_SMALL
            );
            $event->addField(
                $productId,
                'image_' . FrontendProductDatagridListener::PRODUCT_IMAGE_FILTER_LARGE,
                $largeImageUrl
            );
            $event->addField(
                $productId,
                'image_' . FrontendProductDatagridListener::PRODUCT_IMAGE_FILTER_MEDIUM,
                $mediumImageUrl
            );
            $event->addField(
                $productId,
                'image_' . FrontendProductDatagridListener::PRODUCT_IMAGE_FILTER_SMALL,
                $smallImageUrl
            );
        }
    }

    /**
     * Cleans up a unicode string from control characters
     *
     * @param string|array $data
     * @return string
     */
    private function cleanUpStrings($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->cleanUpStrings($value);
            }

            return $data;
        }

        return is_string($data) ? preg_replace(['/[[:cntrl:]]/', '/\s+/'], ' ', $data) : $data;
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

    /**
     * @return AttributeFamilyRepository
     */
    protected function getAttributeFamilyRepository()
    {
        return $this->registry
            ->getManagerForClass(AttributeFamily::class)
            ->getRepository(AttributeFamily::class);
    }
}
