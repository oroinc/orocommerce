<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Search\ProductIndexDataProviderInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

/**
 * Add product related data to search index
 * Main data added from product attributes and some data added manually inside listener
 */
class WebsiteSearchProductIndexerListener
{
    use ContextTrait;

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

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        if (!$this->hasContextFieldGroup($event->getContext(), 'main')) {
            return;
        }

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
            $event->addField($product->getId(), 'sku_uppercase', mb_strtoupper($product->getSku()));
            $event->addField($product->getId(), 'status', $product->getStatus());
            $event->addField($product->getId(), 'type', $product->getType());
            $event->addField($product->getId(), 'is_variant', (int)$product->isVariant());

            if ($product->getAttributeFamily() instanceof AttributeFamily) {
                $event->addField(
                    $product->getId(),
                    'attribute_family_id',
                    $product->getAttributeFamily()->getId()
                );
            }

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

    private function getProductUnitRepository()
    {
        return $this->registry->getRepository(ProductUnit::class);
    }

    /**
     * @return AttributeFamilyRepository
     */
    protected function getAttributeFamilyRepository()
    {
        return $this->registry->getRepository(AttributeFamily::class);
    }
}
