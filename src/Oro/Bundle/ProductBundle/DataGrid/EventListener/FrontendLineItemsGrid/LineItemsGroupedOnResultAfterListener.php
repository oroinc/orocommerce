<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener\FrontendLineItemsGrid;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Adds data needed for displaying grouped line items.
 */
class LineItemsGroupedOnResultAfterListener
{
    /** @var AttachmentManager */
    private $attachmentManager;

    /** @var NumberFormatter */
    private $numberFormatter;

    public function __construct(AttachmentManager $attachmentManager, NumberFormatter $numberFormatter)
    {
        $this->attachmentManager = $attachmentManager;
        $this->numberFormatter = $numberFormatter;
    }

    public function onResultAfter(OrmResultAfter $event): void
    {
        $isGrouped = $this->isGrouped($event->getDatagrid());
        if (!$isGrouped) {
            return;
        }

        foreach ($event->getRecords() as $record) {
            /** @var ProductLineItemInterface[] $lineItems */
            $lineItems = $record->getValue(LineItemsDataOnResultAfterListener::LINE_ITEMS) ?? [];
            if (count($lineItems) <= 1) {
                // Skips simple line items rows.
                continue;
            }

            $firstLineItem = reset($lineItems);
            if (!$firstLineItem instanceof ProductLineItemInterface) {
                continue;
            }

            $parentProduct = $firstLineItem->getParentProduct();
            if (!$parentProduct instanceof Product) {
                throw new \LogicException('Property parentProduct was expected to be not null');
            }

            $parentProductId = $parentProduct->getId();
            $displayedLineItemsIds = explode(',', $record->getValue('displayedLineItemsIds') ?? '');
            $lineItemsData = $record->getValue(LineItemsDataOnResultAfterListener::LINE_ITEMS_DATA) ?? [];
            $firstLineItemData = reset($lineItemsData);
            $unitCode = $firstLineItem->getProductUnitCode();
            $configurableRecordId = $parentProductId . '_' . $unitCode;

            $recordData = [
                'isConfigurable' => true,
                'id' => $configurableRecordId,
                'productId' => $parentProductId,
                'sku' => null,
                'image' => $this->getImageUrl($parentProduct),
                // It is assumed that first sub line item row already has a name of parent product.
                'name' => $firstLineItemData['name'] ?? '',
                'quantity' => 0,
                'unit' => $unitCode,
                // It is assumed that first sub line item already has currency set.
                'currency' => $firstLineItemData['currency'] ?? '',
                'subtotalValue' => 0,
                'discountValue' => 0,
                'subData' => [],
            ];

            foreach ($lineItemsData as $lineItemData) {
                $lineItemData['filteredOut'] = !in_array($lineItemData['id'] ?? 0, $displayedLineItemsIds, false);
                $recordData['subData'][] = $lineItemData;
            }

            $this->processSubtotal($recordData, $lineItemsData);

            foreach ($recordData as $name => $value) {
                $record->setValue($name, $value);
            }
        }
    }

    private function processSubtotal(array &$recordData, array $lineItemsData): void
    {
        foreach ($lineItemsData as $lineItemData) {
            $recordData['quantity'] = $this->sumValuesAsBigDecimal(
                $recordData['quantity'],
                $lineItemData['quantity'] ?? 0
            );

            if (!isset($lineItemData['subtotalValue'], $lineItemData['currency'])) {
                $recordData['subtotalValue'] = $recordData['discountValue'] = null;
            }

            if ($recordData['subtotalValue'] !== null) {
                $recordData['subtotalValue'] = $this->sumValuesAsBigDecimal(
                    $recordData['subtotalValue'],
                    $lineItemData['subtotalValue']
                );
                if (isset($lineItemData['discountValue'])) {
                    $recordData['discountValue'] = $this->sumValuesAsBigDecimal(
                        $recordData['discountValue'],
                        $lineItemData['discountValue']
                    );
                    // It is supposed that subtotalValue already includes applied discount, so we should revert it
                    // to get correct row initial subtotal of configurable product.
                    $recordData['subtotalValue'] = $this->sumValuesAsBigDecimal(
                        $recordData['subtotalValue'],
                        $lineItemData['discountValue']
                    );
                }
            }
        }

        if ($recordData['subtotalValue']) {
            if ($recordData['discountValue']) {
                $recordData['discount'] = $this->formatCurrency('discountValue', $recordData);
                $recordData['initialSubtotal'] = $this->formatCurrency('subtotalValue', $recordData);
                $recordData['subtotalValue'] -= $recordData['discountValue'];
            }

            $recordData['subtotal'] = $this->formatCurrency('subtotalValue', $recordData);
        }
    }

    private function formatCurrency(string $key, array $lineItemData): string
    {
        return $this->numberFormatter->formatCurrency($lineItemData[$key], $lineItemData['currency']);
    }

    private function getImageUrl(Product $product): string
    {
        $image = $product->getImagesByType('listing')->first();

        return $image ? $this->attachmentManager->getFilteredImageUrl($image->getImage(), 'product_small') : '';
    }

    private function isGrouped(DatagridInterface $datagrid): bool
    {
        $parameters = $datagrid->getParameters()->get('_parameters', []);

        return isset($parameters['group']) ? filter_var($parameters['group'], FILTER_VALIDATE_BOOLEAN) : false;
    }

    /**
     * @param BigNumber|int|float|string $valueOne
     * @param BigNumber|int|float|string $valueTwo
     * @return float
     */
    private function sumValuesAsBigDecimal($valueOne, $valueTwo): float
    {
        return BigDecimal::of($valueOne)
            ->plus(
                BigDecimal::of($valueTwo)
            )
            ->toFloat();
    }
}
