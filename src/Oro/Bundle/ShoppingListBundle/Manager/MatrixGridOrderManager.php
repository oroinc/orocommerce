<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollection;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionColumn;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionRow;

class MatrixGridOrderManager
{
    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @var ProductVariantAvailabilityProvider
     */
    private $variantAvailability;

    /**
     * @var array|MatrixCollection[]
     */
    private $collectionCache = [];

    /**
     * @param PropertyAccessor $propertyAccessor
     * @param ProductVariantAvailabilityProvider $variantAvailability
     */
    public function __construct(
        PropertyAccessor $propertyAccessor,
        ProductVariantAvailabilityProvider $variantAvailability
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->variantAvailability = $variantAvailability;
    }

    /**
     * @param Product $product
     * @return MatrixCollection
     */
    public function getMatrixCollection(Product $product)
    {
        if (isset($this->collectionCache[$product->getId()])) {
            return $this->collectionCache[$product->getId()];
        }

        $variantFields = $this->getVariantFields($product);
        $availableVariants = $this->getAvailableVariants($product, $variantFields);

        $collection = new MatrixCollection();
        $collection->unit = $product->getPrimaryUnitPrecision()->getUnit();

        foreach ($variantFields[0]['values'] as $firstValue) {
            $row = new MatrixCollectionRow();
            $row->label = $firstValue['label'];

            if (count($variantFields) == 1) {
                $column = new MatrixCollectionColumn();
                if (isset($availableVariants[$firstValue['value']]['_product'])) {
                    $column->product = $availableVariants[$firstValue['value']]['_product'];
                }

                $row->columns = [$column];
            } else {
                foreach ($variantFields[1]['values'] as $secondValue) {
                    $column = new MatrixCollectionColumn();
                    $column->label = $secondValue['label'];

                    if (isset($availableVariants[$firstValue['value']][$secondValue['value']]['_product'])) {
                        $column->product = $availableVariants[$firstValue['value']][$secondValue['value']]['_product'];
                    }

                    $row->columns[] = $column;
                }
            }

            $collection->rows[] = $row;
        }

        return $this->collectionCache[$product->getId()] = $collection;
    }

    /**
     * Get variant fields with values for product
     *
     * @param Product $product
     * @return array ex.: [['name' => 'color', 'values' => [['value' => 'red', 'label' => 'Red'], ...]], ...]
     */
    private function getVariantFields(Product $product)
    {
        $variantFields = [];

        $fieldNames = array_keys($this->variantAvailability->getVariantFieldsWithAvailability($product));
        foreach ($fieldNames as $fieldName) {
            $values = $this->variantAvailability->getAllVariantsByVariantFieldName($fieldName);

            $formattedValues = [];
            foreach ($values as $value => $label) {
                $formattedValues[] = [
                    'value' => $value,
                    'label' => $label,
                ];
            }

            $variantFields[] = [
                'name' => $fieldName,
                'values' => $formattedValues
            ];
        }

        return $variantFields;
    }

    /**
     * Get all available variants for product grouped by variant field[s] value[s]
     *
     * @param Product $product
     * @param array $variantFields
     * @return array ex.: ['red' => ['xxl' => ['product' => object(Product)#1], ...], ...]
     */
    private function getAvailableVariants(Product $product, array $variantFields)
    {
        $availableVariants = [];

        $variants = $this->variantAvailability->getSimpleProductsByVariantFields($product);
        foreach ($variants as $variant) {
            if (!$this->doSimpleProductSupportsUnitPrecision($variant, $product->getPrimaryUnitPrecision())) {
                continue;
            }

            $values = [];
            foreach ($variantFields as $field) {
                $values[] = '['.$this->variantAvailability->getVariantFieldValue($variant, $field['name']).']';
            }
            $values[] = '[_product]';

            $this->propertyAccessor->setValue($availableVariants, implode('', $values), $variant);
        }

        return $availableVariants;
    }

    /**
     * @param Product $product
     * @param ProductUnitPrecision $unit
     * @return bool
     */
    private function doSimpleProductSupportsUnitPrecision(Product $product, ProductUnitPrecision $unit)
    {
        $productUnits = $product->getUnitPrecisions()->map(
            function (ProductUnitPrecision $unitPrecision) {
                return $unitPrecision->getUnit();
            }
        );

        return $productUnits->contains($unit->getUnit());
    }

    /**
     * @param MatrixCollection $collection
     *
     * @return array|LineItem[]
     */
    public function convertMatrixIntoLineItems(MatrixCollection $collection)
    {
        $lineItems = [];

        /** @var MatrixCollectionRow $row */
        foreach ($collection->rows as $row) {
            /** @var MatrixCollectionColumn $column */
            foreach ($row->columns as $column) {
                if ($column->product && $column->quantity) {
                    $lineItems[] = (new LineItem())
                        ->setProduct($column->product)
                        ->setQuantity($column->quantity)
                        ->setUnit($collection->unit);
                }
            }
        }

        return $lineItems;
    }
}
