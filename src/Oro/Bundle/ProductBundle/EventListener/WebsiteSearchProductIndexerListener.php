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
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Search\ProductIndexDataProviderInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

/**
 * Add product related data to search index
 * Main data added from product attributes and some data added manually inside listener
 */
class WebsiteSearchProductIndexerListener implements WebsiteSearchProductIndexerListenerInterface
{
    use ContextTrait;

    private WebsiteContextManager $websiteContextManager;
    private AbstractWebsiteLocalizationProvider $websiteLocalizationProvider;
    private ManagerRegistry $doctrine;
    private AttachmentManager $attachmentManager;
    private AttributeManager $attributeManager;
    private ProductIndexDataProviderInterface $dataProvider;
    private int $batchSize = 100;

    public function __construct(
        AbstractWebsiteLocalizationProvider $websiteLocalizationProvider,
        WebsiteContextManager $websiteContextManager,
        ManagerRegistry $doctrine,
        AttributeManager $attributeManager,
        ProductIndexDataProviderInterface $dataProvider
    ) {
        $this->websiteLocalizationProvider = $websiteLocalizationProvider;
        $this->websiteContextManager = $websiteContextManager;
        $this->doctrine = $doctrine;
        $this->attributeManager = $attributeManager;
        $this->dataProvider = $dataProvider;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function onWebsiteSearchIndex(IndexEntityEvent $event): void
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
        $attributeFamilies = $this->getAttributeFamilies($attributes, $website);

        $countVariantLinks = 0;
        $batchSize = 2000;

        foreach ($products as $product) {
            $countVariantLinks += $product->getVariantLinks()->count();
            $productId = $product->getId();

            foreach ($attributes as $attribute) {
                if (!$this->isAllowedToIndex($attribute, $product, $attributeFamilies)) {
                    continue;
                }

                $data = $this->dataProvider->getIndexData($product, $attribute, $localizations);
                $this->processIndexData($event, $productId, $data);
            }

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

            if (\array_key_exists($product->getId(), $productUnits)) {
                $event->addField(
                    $product->getId(),
                    'product_units',
                    serialize($productUnits[$product->getId()])
                );
            }

            if ($product->isConfigurable()) {
                $event->addField(
                    $product->getId(),
                    'variant_fields_count',
                    count($product->getVariantFields())
                );
            }

            if ($countVariantLinks > $batchSize) {
                $this->doctrine->getManager()->clear();
                $countVariantLinks = 0;
            }
        }
    }

    public function setBatchSize(int $batchSize): void
    {
        $this->batchSize = $batchSize;
    }

    private function getWebsite(IndexEntityEvent $event): ?Website
    {
        $websiteId = $this->websiteContextManager->getWebsiteId($event->getContext());
        if ($websiteId) {
            return $this->doctrine->getManagerForClass(Website::class)->find(Website::class, $websiteId);
        }

        return null;
    }

    private function processIndexData(
        IndexEntityEvent $event,
        int $productId,
        iterable $data
    ): void {
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

    private function isAllowedToIndex(FieldConfigModel $attribute, Product $product, array $attributeFamilies): bool
    {
        if ($this->attributeManager->isSystem($attribute)) {
            return true;
        }

        if (!isset($attributeFamilies[$attribute->getId()])) {
            return false;
        }

        $attributeFamily = $product->getAttributeFamily();

        return
            null === $attributeFamily
            || \in_array($attributeFamily->getId(), $attributeFamilies[$attribute->getId()], true);
    }

    /**
     * Cleans up a unicode string from control characters
     */
    private function cleanUpStrings(mixed $data): mixed
    {
        if (\is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->cleanUpStrings($value);
            }

            return $data;
        }

        return \is_string($data) && $data
            ? preg_replace(['/[[:cntrl:]]/', '/\s+/'], ' ', $data)
            : $data;
    }

    private function getProductUnitRepository(): ProductUnitRepository
    {
        return $this->doctrine->getRepository(ProductUnit::class);
    }

    private function getAttributeFamilyRepository(): AttributeFamilyRepository
    {
        return $this->doctrine->getRepository(AttributeFamily::class);
    }

    private function getAttributeFamilies(array $attributes, Website $website): array
    {
        $organization = $website->getOrganization();
        $attributeFamilyRepo = $this->getAttributeFamilyRepository();

        if (count($attributes) <= $this->batchSize) {
            $attributeFamilies = $attributeFamilyRepo
                ->getFamilyIdsForAttributesByOrganization($attributes, $organization);
        } else {
            $attributeFamilies = array_reduce(
                array_chunk($attributes, $this->batchSize),
                function (array $accum, array $currentAttrChunk) use ($organization, $attributeFamilyRepo): array {
                    $accum += $attributeFamilyRepo
                        ->getFamilyIdsForAttributesByOrganization($currentAttrChunk, $organization);

                    return $accum;
                },
                []
            );
        }

        return $attributeFamilies;
    }
}
