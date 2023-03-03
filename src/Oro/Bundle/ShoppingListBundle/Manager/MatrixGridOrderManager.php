<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollection;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionColumn;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionRow;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Provides matrix product collection which will used to update configurable products.
 */
class MatrixGridOrderManager
{
    private PropertyAccessorInterface $propertyAccessor;
    private ProductVariantAvailabilityProvider $variantAvailability;
    private EmptyMatrixGridInterface $emptyMatrixGridManager;
    private ManagerRegistry $doctrine;

    /**
     * @var array|MatrixCollection[]
     */
    private array $collectionCache = [];

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        ProductVariantAvailabilityProvider $variantAvailability,
        EmptyMatrixGridInterface $emptyMatrixGridManager,
        ManagerRegistry $doctrine
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->variantAvailability = $variantAvailability;
        $this->emptyMatrixGridManager = $emptyMatrixGridManager;
        $this->doctrine = $doctrine;
    }

    /**
     * @param Product           $product
     * @param ShoppingList|null $shoppingList
     *
     * @return MatrixCollection
     */
    public function getMatrixCollection(Product $product, ShoppingList $shoppingList = null)
    {
        $shoppingListId = $shoppingList?->getId();
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

        $collection->columns = $variantFields[1]['values'] ?? $variantFields[0]['values'] ;
        $collection->dimensions = \count($variantFields);

        foreach ($variantFields[0]['values'] as $firstValue) {
            $row = new MatrixCollectionRow();
            $row->label = $firstValue['label'];

            if ($collection->dimensions == 1) {
                $column = new MatrixCollectionColumn();
                if (isset($availableVariants[$firstValue['value']]['_product'])) {
                    $column->product = $availableVariants[$firstValue['value']]['_product'];
                    $column->quantity = $this->getQuantity(
                        $product->getPrimaryUnitPrecision()->getUnit(),
                        $column->product,
                        $shoppingList
                    );
                }
                $row->columns = [$column];
            } else {
                foreach ($variantFields[1]['values'] as $i => $secondValue) {
                    if (isset($availableVariants[$firstValue['value']][$secondValue['value']]['_product'])) {
                        $column = new MatrixCollectionColumn();
                        $column->label = $secondValue['label'];
                        $column->product = $availableVariants[$firstValue['value']][$secondValue['value']]['_product'];
                        $column->quantity = $this->getQuantity(
                            $product->getPrimaryUnitPrecision()->getUnit(),
                            $column->product,
                            $shoppingList
                        );
                        $row->columns[$i] = $column;
                    }
                }
            }

            $collection->rows[] = $row;
        }

        return $this->collectionCache[$product->getId()][$shoppingListId] = $collection;
    }

    /**
     * @param Product $product
     * @param ProductUnit $unit
     * @param ShoppingList $shoppingList
     *
     * @return MatrixCollection
     */
    public function getMatrixCollectionForUnit(Product $product, ProductUnit $unit, ShoppingList $shoppingList)
    {
        $shoppingListId = $shoppingList->getId();
        $unitCode = $unit->getCode();
        if (isset($this->collectionCache[$product->getId()][$unitCode][$shoppingListId])) {
            return $this->collectionCache[$product->getId()][$unitCode][$shoppingListId];
        }

        $variantFields = $this->getVariantFields($product);
        $availableVariants = $this->getAvailableVariants($product, $variantFields, $unit);

        $collection = new MatrixCollection();
        $collection->unit = $unit;

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
                    $column->quantity = $this->getQuantity($unit, $column->product, $shoppingList);
                }

                $row->columns = [$column];
            } else {
                foreach ($variantFields[1]['values'] as $secondValue) {
                    $column = new MatrixCollectionColumn();
                    $column->label = $secondValue['label'];

                    if (isset($availableVariants[$firstValue['value']][$secondValue['value']]['_product'])) {
                        $column->product = $availableVariants[$firstValue['value']][$secondValue['value']]['_product'];
                        $column->quantity = $this->getQuantity($unit, $column->product, $shoppingList);
                    }

                    $row->columns[] = $column;
                }
            }

            $collection->rows[] = $row;
        }

        return $this->collectionCache[$product->getId()][$unitCode][$shoppingListId] = $collection;
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
     * @param ProductUnit|null $unit
     * @return array ex.: ['red' => ['xxl' => ['product' => object(Product)#1], ...], ...]
     * @throws \InvalidArgumentException
     */
    private function getAvailableVariants(Product $product, array $variantFields, ProductUnit $unit = null)
    {
        if (!$unit) {
            $unit = $product->getPrimaryUnitPrecision()->getUnit();
        }

        $availableVariants = [];

        $variants = $this->variantAvailability->getSimpleProductsByVariantFields($product);
        $variantIdsSupportUnit = $this->getProductIdsSupportUnit($variants, $unit);
        foreach ($variants as $variant) {
            if (false === \in_array($variant->getId(), $variantIdsSupportUnit, true)) {
                continue;
            }

            $values = [];
            foreach ($variantFields as $field) {
                $value = $this->variantAvailability->getVariantFieldScalarValue($variant, $field['name']);
                // Skip product from available variants if one of variant fields is null
                if ($value === null) {
                    continue 2;
                }
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

    private function getProductIdsSupportUnit(array $products, ProductUnit $unit): array
    {
        /** @var ProductUnitRepository $productUnitRepository */
        $productUnitRepository = $this->doctrine->getRepository(ProductUnit::class);

        return $productUnitRepository->getProductIdsSupportUnit($products, $unit);
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
     * @param ProductUnit       $productUnit
     * @param Product           $cellProduct
     * @param ShoppingList|null $shoppingList
     *
     * @return float|null
     */
    private function getQuantity(ProductUnit $productUnit, Product $cellProduct, ShoppingList $shoppingList = null)
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
                && $lineItem->getProductUnitCode() === $productUnit->getCode()
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
