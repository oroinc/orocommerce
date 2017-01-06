<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
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
     * @var TotalProcessorProvider
     */
    private $totalProvider;

    /**
     * @param PropertyAccessor $propertyAccessor
     * @param ProductVariantAvailabilityProvider $variantAvailability
     * @param TotalProcessorProvider $totalProvider
     */
    public function __construct(
        PropertyAccessor $propertyAccessor,
        ProductVariantAvailabilityProvider $variantAvailability,
        TotalProcessorProvider $totalProvider
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->variantAvailability = $variantAvailability;
        $this->totalProvider = $totalProvider;
    }

    /**
     * Get variant fields with values for product
     *
     * @param Product $product
     * @return array ex.: [['name' => 'color', 'values' => [['value' => 'red', 'label' => 'Red'], ...]], ...]
     */
    public function getVariantFields(Product $product)
    {
        $variantFields = [];
        foreach (array_keys($this->variantAvailability->getVariantFieldsWithAvailability($product)) as $field) {
            $values = $this->variantAvailability->getAllVariantsByVariantFieldName($field);

            $formattedValues = [];
            foreach ($values as $value => $label) {
                $formattedValues[] = [
                    'value' => $value,
                    'label' => $label,
                ];
            }

            $variantFields[] = [
                'name' => $field,
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
        $variants = $this->variantAvailability->getSimpleProductsByVariantFields($product);

        $availableVariants = [];
        foreach ($variants as $variant) {
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
     * @param array $variantFields
     * @return MatrixCollection
     */
    public function createMatrixCollection(Product $product, array $variantFields)
    {
        $availableVariants = $this->getAvailableVariants($product, $variantFields);

        $collection = new MatrixCollection();
        $collection->unit = $product->getPrimaryUnitPrecision()->getUnit();

        foreach ($variantFields[0]['values'] as $firstValue) {
            $row = new MatrixCollectionRow();

            if (count($variantFields) == 1) {
                $column = new MatrixCollectionColumn();
                if (isset($availableVariants[$firstValue['value']]['_product'])) {
                    $column->product = $availableVariants[$firstValue['value']]['_product'];
                }

                $row->columns = [$column];
            } else {
                foreach ($variantFields[1]['values'] as $secondValue) {
                    $column = new MatrixCollectionColumn();
                    if (isset($availableVariants[$firstValue['value']][$secondValue['value']]['_product'])) {
                        $column->product = $availableVariants[$firstValue['value']][$secondValue['value']]['_product'];
                    }

                    $row->columns[] = $column;
                }
            }

            $collection->rows[] = $row;
        }

        return $collection;
    }

    /**
     * Get total quantities for all columns and per column
     *
     * @param MatrixCollection $collection
     * @return array ex.: ['total' => 5, 'columns' => [2, 6, ...]]
     */
    public function calculateTotalQuantities(MatrixCollection $collection)
    {
        $totalQuantities = 0;
        $quantitiesByColumn = array_fill(0, count($collection->rows[0]->columns), 0);

        foreach ($collection->rows as $row) {
            foreach ($row->columns as $i => $column) {
                $totalQuantities += $column->quantity;
                $quantitiesByColumn[$i] += $column->quantity;
            }
        }

        return ['total' => $totalQuantities, 'columns' => $quantitiesByColumn];
    }

    /**
     * @param MatrixCollection $collection
     * @return Price
     */
    public function calculateTotalPrice(MatrixCollection $collection)
    {
        $shoppingList = new ShoppingList();

        foreach ($collection->rows as $row) {
            foreach ($row->columns as $column) {
                if ($column->product === null) {
                    continue;
                }

                $lineItem = new LineItem();
                $lineItem->setProduct($column->product);
                $lineItem->setUnit($collection->unit);
                $lineItem->setQuantity($column->quantity);

                $shoppingList->addLineItem($lineItem);
            }
        }

        return $this->totalProvider->getTotal($shoppingList)->getTotalPrice();
    }
}
