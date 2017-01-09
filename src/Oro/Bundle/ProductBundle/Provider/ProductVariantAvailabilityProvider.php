<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Event\RestrictProductVariantEvent;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ProductVariantAvailabilityProvider
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EnumValueProvider */
    protected $enumValueProvider;

    /** @var CustomFieldProvider */
    protected $customFieldProvider;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EnumValueProvider $enumValueProvider
     * @param CustomFieldProvider $customFieldProvider
     * @param PropertyAccessor $propertyAccessor
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EnumValueProvider $enumValueProvider,
        CustomFieldProvider $customFieldProvider,
        PropertyAccessor $propertyAccessor,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->enumValueProvider = $enumValueProvider;
        $this->customFieldProvider = $customFieldProvider;
        $this->propertyAccessor = $propertyAccessor;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Product $configurableProduct
     * @param array $variantParameters
     * @return array
     */
    public function getVariantFieldsWithAvailability(Product $configurableProduct, array $variantParameters = [])
    {
        $this->ensureProductTypeIsConfigurable($configurableProduct);

        $availableVariants = $this->getVariantFields($configurableProduct);

        foreach ($variantParameters as $variantField => $variantValue) {
            $this->filterVariants($availableVariants, $configurableProduct, $variantParameters, $variantField);
        }

        return $availableVariants;
    }

    /**
     * @param array $availableVariants
     * @param Product $configurableProduct
     * @param array $variantParameters
     * @param string $currentField
     */
    protected function filterVariants(
        &$availableVariants,
        Product $configurableProduct,
        $variantParameters,
        $currentField
    ) {
        $currentVariants = $this->getVariantFields(
            $configurableProduct,
            [
                $currentField => $variantParameters[$currentField]
            ]
        );

        foreach ($availableVariants as $variantField => &$variantValues) {
            if ($variantField === $currentField) {
                continue;
            }

            array_walk(
                $variantValues,
                function (&$item, $key, $currentValues) {
                    $item = $item && $currentValues[$key];
                },
                $currentVariants[$variantField]
            );
        }
    }

    /**
     * @param Product $configurableProduct
     * @param array $variantParameters
     * @return array
     */
    protected function getVariantFields(Product $configurableProduct, $variantParameters = [])
    {
        $variantFields = $configurableProduct->getVariantFields();

        $availableSimpleProducts = $this->getSimpleProductsByVariantFields(
            $configurableProduct,
            $variantParameters
        );

        $allVariants = [];
        foreach ($variantFields as $variantField) {
            // get array of all variants
            $allVariants[$variantField] = $this->getAllVariantsByVariantFieldName($variantField);
            $fieldType = $this->getFieldType($variantField);

            foreach ($availableSimpleProducts as $simpleProduct) {
                $variantValue = $this->propertyAccessor->getValue($simpleProduct, $variantField);

                switch ($fieldType) {
                    case 'enum':
                        $id = $this->doctrineHelper->getSingleEntityIdentifier($variantValue);
                        if ($id === null) {
                            continue;
                        }
                        $allVariants[$variantField][$id] = true;
                        break;

                    case 'boolean':
                        $allVariants[$variantField][$variantValue] = true;
                        break;
                }
            }
        }

        return $allVariants;
    }

    /**
     * @param string $variantFieldName
     * @return array
     */
    protected function getAllVariantsByVariantFieldName($variantFieldName)
    {
        $type = $this->getFieldType($variantFieldName);

        $variants = [];
        switch ($type) {
            case 'enum':
                $enumCode = ExtendHelper::generateEnumCode(Product::class, $variantFieldName);
                $variants = $this->enumValueProvider->getEnumChoicesByCode($enumCode);
                break;

            case 'boolean':
                // TODO: Is it possible to have this choice variants in one place?
                $variants = ['No', 'Yes'];
                break;
        }

        return array_fill_keys(array_keys($variants), false);
    }

    /**
     * @param string $fieldName
     * @return string|null
     */
    protected function getFieldType($fieldName)
    {
        $customFields = $this->customFieldProvider->getEntityCustomFields(Product::class);

        // TODO: Add cache?
        return array_key_exists($fieldName, $customFields) ? $customFields[$fieldName]['type'] : null;
    }

    /**
     * @param Product $configurableProduct
     * @param array $variantParameters
     * $variantParameters = [
     *     'size' => 'm',
     *     'color' => 'red',
     *     'slim_fit' => true
     * ]
     * Value is extended field id for select field and true or false for boolean field
     * @return Product[]
     */
    public function getSimpleProductsByVariantFields(Product $configurableProduct, array $variantParameters = [])
    {
        $this->ensureProductTypeIsConfigurable($configurableProduct);

        /** @var ProductRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(Product::class);

        $qb = $repository->getSimpleProductsByVariantFieldsQueryBuilder($configurableProduct, $variantParameters);

        $restrictProductVariantEvent = new RestrictProductVariantEvent($qb);
        $this->eventDispatcher->dispatch(RestrictProductVariantEvent::NAME, $restrictProductVariantEvent);

        return $restrictProductVariantEvent
            ->getQueryBuilder()
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Product $configurableProduct
     * @param array $variantParameters
     * @return Product
     * @throws \InvalidArgumentException
     */
    public function getSimpleProductByVariantFields(Product $configurableProduct, array $variantParameters = [])
    {
        $this->ensureProductTypeIsConfigurable($configurableProduct);
        $simpleProducts = $this->getSimpleProductsByVariantFields($configurableProduct, $variantParameters);

        if (count($simpleProducts) !== 1) {
            throw new \InvalidArgumentException('Variant values provided don\'t match exactly one simple product');
        }

        return reset($simpleProducts);
    }

    /**
     * @param Product $product
     * @throws \InvalidArgumentException
     */
    private function ensureProductTypeIsConfigurable(Product $product)
    {
        if (!$product->isConfigurable()) {
            throw new \InvalidArgumentException(
                sprintf('Product with type "%s" expected, "%s" given', Product::TYPE_CONFIGURABLE, $product->getType())
            );
        }
    }
}
