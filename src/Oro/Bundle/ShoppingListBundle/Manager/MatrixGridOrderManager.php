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
     * @var EmptyMatrixGridInterface
     */
    private $emptyMatrixGridManager;

    /**
     * @var array|MatrixCollection[]
     */
    private $collectionCache = [];

    /**
     * @param PropertyAccessor $propertyAccessor
     * @param ProductVariantAvailabilityProvider $variantAvailability
     * @param EmptyMatrixGridInterface $emptyMatrixGridManager
     */
    public function __construct(
        PropertyAccessor $propertyAccessor,
        ProductVariantAvailabilityProvider $variantAvailability,
        EmptyMatrixGridInterface $emptyMatrixGridManager
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->variantAvailability = $variantAvailability;
        $this->emptyMatrixGridManager = $emptyMatrixGridManager;
    }

    /**
     * @param Product           $product
     * @param ShoppingList|null $shoppingList
     *
     * @return MatrixCollection
     */
    public function getMatrixCollection(Product $product, ShoppingList $shoppingList = null)
    {
        $shoppingListId = $shoppingList ? $shoppingList->getId() : null;
        if (isset($this->collectionCache[$product->getId()][$shoppingListId])) {
            return $this->collectionCache[$product->getId()][$shoppingListId];
        }

        $variantFields = $this->getVariantFields($product);
        $availableVariants = $this->getAvailableVariants($product, $variantFields);

        $collection = new MatrixCollection();
        $collection->unit = $product->getPrimaryUnitPrecision()->getUnit();

        if (!isset($variantFields[0])) {
            return $collection;
        }

        foreach ($variantFields[0]['values'] as $firstValue) {
            $row = new MatrixCollectionRow();
            $row->label = $firstValue['label'];

            if (count($variantFields) == 1) {
                $column = new MatrixCollectionColumn();
                if (isset($availableVariants[$firstValue['value']]['_product'])) {
                    $column->product = $availableVariants[$firstValue['value']]['_product'];
                    $column->quantity = $this->getQuantity($product, $column->product, $shoppingList);
                }

                $row->columns = [$column];
            } else {
                foreach ($variantFields[1]['values'] as $secondValue) {
                    $column = new MatrixCollectionColumn();
                    $column->label = $secondValue['label'];

                    if (isset($availableVariants[$firstValue['value']][$secondValue['value']]['_product'])) {
                        $column->product = $availableVariants[$firstValue['value']][$secondValue['value']]['_product'];
                        $column->quantity = $this->getQuantity($product, $column->product, $shoppingList);
                    }

                    $row->columns[] = $column;
                }
            }

            $collection->rows[] = $row;
        }

        return $this->collectionCache[$product->getId()][$shoppingListId] = $collection;
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

        $fieldNames = array_keys($this->variantAvailability->getVariantFieldsAvailability($product));
        foreach ($fieldNames as $fieldName) {
            $values = $this->variantAvailability->getVariantFieldValues($fieldName);

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
                $value = $this->variantAvailability->getVariantFieldScalarValue($variant, $field['name']);
                if (is_bool($value)) {
                    $value = ($value) ? '1' : '0';
                }
                $values[] = "[$value]";
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
     * @param Product          $product
     * @param array            $requiredCollection Matrix collection from a request
     *
     * @return array|LineItem[]
     */
    public function convertMatrixIntoLineItems(MatrixCollection $collection, Product $product, $requiredCollection)
    {
        $lineItems = [];
        $rowIds    = [];

        // For partial operations, we must use required rows only
        if (isset($requiredCollection['rows'])) {
            $rowIds = array_keys($requiredCollection['rows']);
        }

        /** @var MatrixCollectionRow $row */
        foreach ($collection->rows as $rowIndex => $row) {
            if (in_array($rowIndex, $rowIds, true)) {
                /** @var MatrixCollectionColumn $column */
                foreach ($row->columns as $column) {
                    if ($column->product) {
                        $lineItem = new LineItem();
                        $lineItem->setProduct($column->product);
                        $lineItem->setQuantity((float) $column->quantity);
                        $lineItem->setUnit($collection->unit);

                        if ($product->isConfigurable()) {
                            $lineItem->setParentProduct($product);
                        }

                        $lineItems[] = $lineItem;
                    }
                }
            }
        }

        return $lineItems;
    }

    /**
     * Get MatrixCollectionColumn's quantity by shopping list line items
     *
     * @param Product           $parentProduct
     * @param Product           $cellProduct
     * @param ShoppingList|null $shoppingList
     *
     * @return float|null
     */
    private function getQuantity(Product $parentProduct, Product $cellProduct, ShoppingList $shoppingList = null)
    {
        if (!$shoppingList) {
            return null;
        }
        $lineItems = $shoppingList->getLineItems();
        if ($lineItems->isEmpty()) {
            return null;
        }

        /** @var LineItem $lineItem */
        foreach ($lineItems->getIterator() as $lineItem) {
            if ($cellProduct->getId() === $lineItem->getProduct()->getId()
                && $lineItem->getProductUnitCode() === $parentProduct->getPrimaryUnitPrecision()->getProductUnitCode()
            ) {
                return $lineItem->getQuantity();
            }
        }

        return null;
    }

    /**
     * @param ShoppingList $shoppingList
     * @param Product $product
     * @param LineItem[] $lineItems
     */
    public function addEmptyMatrixIfAllowed(ShoppingList $shoppingList, Product $product, array $lineItems)
    {
        if ($this->emptyMatrixGridManager->isAddEmptyMatrixAllowed($lineItems)) {
            $this->emptyMatrixGridManager->addEmptyMatrix($shoppingList, $product);
        }
    }
}
