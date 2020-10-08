<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\EventListener;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ConfigurableProductProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Event\LineItemDataBuildEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Populates line item records by required data.
 */
class FrontendLineItemsGridEventListener
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var NumberFormatter */
    private $numberFormatter;

    /** @var AttachmentManager */
    private $attachmentManager;

    /** @var ConfigurableProductProvider */
    private $configurableProductProvider;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param EventDispatcherInterface $eventDispatcher
     * @param NumberFormatter $numberFormatter
     * @param AttachmentManager $attachmentManager
     * @param ConfigurableProductProvider $configurableProductProvider
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        EventDispatcherInterface $eventDispatcher,
        NumberFormatter $numberFormatter,
        AttachmentManager $attachmentManager,
        ConfigurableProductProvider $configurableProductProvider,
        LocalizationHelper $localizationHelper
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->eventDispatcher = $eventDispatcher;
        $this->numberFormatter = $numberFormatter;
        $this->attachmentManager = $attachmentManager;
        $this->configurableProductProvider = $configurableProductProvider;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event): void
    {
        $lineItems = $this->getLineItems($event);
        if (!$lineItems) {
            return;
        }

        $lineItemDataBuildEvent = new LineItemDataBuildEvent($lineItems, ['datagrid' => $event->getDatagrid()]);
        $this->eventDispatcher->dispatch($lineItemDataBuildEvent, LineItemDataBuildEvent::NAME);

        foreach ($event->getRecords() as $record) {
            $rowId = $this->getRowId($record);
            /** @var LineItem[] $recordLineItems */
            $recordLineItems = array_intersect_key($lineItems, array_flip(explode(',', $rowId)));
            $record->setValue('id', $rowId);

            if ($record->getValue('isConfigurable')) {
                $this->processConfigurableProduct($record, $recordLineItems, $lineItemDataBuildEvent);
            } else {
                $this->processSimpleProduct($record, reset($recordLineItems), $lineItemDataBuildEvent);
            }
        }
    }

    /**
     * @param ResultRecordInterface $record
     * @return string
     */
    private function getRowId(ResultRecordInterface $record): string
    {
        return (string)($record->getValue('allLineItemsIds') ?: $record->getValue('id'));
    }

    /**
     * @param LineItem $lineItem
     * @return array
     */
    private function getDefaultLineItemData(LineItem $lineItem): array
    {
        $product = $lineItem->getProduct();

        return [
            'id' => $lineItem->getId(),
            'productId' => $product->getId(),
            'sku' => $product->getSku(),
            'quantity' => $lineItem->getQuantity(),
            'unit' => $lineItem->getProductUnitCode(),
            'notes' => $lineItem->getNotes(),
            'image' => $this->getImageUrl($product),
        ];
    }

    /**
     * @param ResultRecordInterface $record
     * @param LineItem $lineItem
     * @param LineItemDataBuildEvent $lineItemDataBuildEvent
     */
    private function processSimpleProduct(
        ResultRecordInterface $record,
        LineItem $lineItem,
        LineItemDataBuildEvent $lineItemDataBuildEvent
    ): void {
        $defaultLineItemData = $this->getDefaultLineItemData($lineItem);
        $eventLineItemData = $lineItemDataBuildEvent->getDataForLineItem($defaultLineItemData['id']);

        $lineItemData = array_merge(
            $defaultLineItemData,
            [
                'name' => (string)$this->localizationHelper->getLocalizedValue($lineItem->getProduct()->getNames()),
                'link' => $this->urlGenerator
                    ->generate('oro_product_frontend_product_view', ['id' => $defaultLineItemData['productId']]),
            ],
            $eventLineItemData
        );

        foreach ($lineItemData as $name => $value) {
            $record->setValue($name, $value);
        }
    }

    /**
     * @param ResultRecordInterface $record
     * @param LineItem[] $lineItems
     * @param LineItemDataBuildEvent $lineItemDataBuildEvent
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function processConfigurableProduct(
        ResultRecordInterface $record,
        array $lineItems,
        LineItemDataBuildEvent $lineItemDataBuildEvent
    ): void {
        $rowQuantity = $rowSubtotal = $rowDiscount = 0.0;
        $currency = null;
        $lineItemsData = [];
        $displayed = explode(',', $record->getValue('displayedLineItemsIds'));

        /** @var LineItem $firstLineItem */
        $firstLineItem = reset($lineItems);
        $parentProduct = $firstLineItem->getParentProduct() ?: $firstLineItem->getProduct();
        $name = (string)$this->localizationHelper->getLocalizedValue($parentProduct->getNames());

        foreach ($lineItems as $lineItem) {
            $defaultLineItemData = $this->getDefaultLineItemData($lineItem);
            $eventLineItemData = $lineItemDataBuildEvent->getDataForLineItem($defaultLineItemData['id']);
            $lineItemData = array_merge(
                $defaultLineItemData,
                [
                    'name' => $name,
                    'productConfiguration' => $this->getVariantFieldsValuesForLineItem($lineItem),
                    'filteredOut' => !in_array($defaultLineItemData['id'], $displayed, false),
                    'action_configuration' => [
                        'add_notes' => !$lineItem->getNotes(),
                        'edit_notes' => false,
                        'update_configurable' => false,
                    ],
                ],
                $eventLineItemData
            );

            $rowQuantity += $lineItemData['quantity'];

            if (!isset($lineItemData['subtotalValue'], $lineItemData['currency'])) {
                $rowSubtotal = $rowDiscount = null;
            }

            if ($rowSubtotal !== null) {
                $currency = $lineItemData['currency'];
                $rowSubtotal += $lineItemData['subtotalValue'];

                if (isset($lineItemData['discountValue'])) {
                    $rowDiscount += $lineItemData['discountValue'];
                    // It is supposed that subtotalValue already includes applied discount, so we should revert it
                    // to get correct row initial subtotal of configurable product.
                    $rowSubtotal += $lineItemData['discountValue'];
                }
            }

            $lineItemsData[] = $lineItemData;
        }

        $parentProductId = $parentProduct->getId();
        if (count($lineItems) === 1) {
            $lineItemData['link'] = $this->urlGenerator->generate(
                'oro_product_frontend_product_view',
                ['id' => $parentProductId, 'variantProductId' => $firstLineItem->getProduct()->getId()]
            );

            foreach ($lineItemData as $name => $value) {
                $record->setValue($name, $value);
            }

            if (!$firstLineItem->getProduct()->isConfigurable()) {
                $record->setValue('isConfigurable', false);
            }
        } else {
            $record->setValue('id', $parentProductId . '_' . $firstLineItem->getProductUnitCode());
            $record->setValue('productId', $parentProductId);
            $record->setValue('sku', null);
            $record->setValue('image', $this->getImageUrl($parentProduct));
            $record->setValue('name', $name);
            $record->setValue('subData', $lineItemsData);
            $record->setValue('quantity', $rowQuantity);
            $record->setValue('unit', $firstLineItem->getProductUnitCode());

            if ($rowSubtotal) {
                if ($rowDiscount) {
                    $record->setValue('discount', $this->numberFormatter->formatCurrency($rowDiscount, $currency));
                    $record->setValue(
                        'initialSubtotal',
                        $this->numberFormatter->formatCurrency($rowSubtotal, $currency)
                    );
                    $rowSubtotal -= $rowDiscount;
                }

                $record->setValue('subtotal', $this->numberFormatter->formatCurrency($rowSubtotal, $currency));
            }

            $record->setValue(
                'link',
                $this->urlGenerator->generate('oro_product_frontend_product_view', ['id' => $parentProductId])
            );
            $record->setValue(
                'deleteLink',
                $this->urlGenerator->generate(
                    'oro_api_shopping_list_frontend_delete_line_item_configurable',
                    ['productId' => $parentProductId, 'unitCode' => $firstLineItem->getProductUnitCode()]
                )
            );
        }
    }

    /**
     * @param Product $product
     * @return string
     */
    private function getImageUrl(Product $product): string
    {
        $image = $product->getImagesByType('listing')->first();

        return $image ? $this->attachmentManager->getFilteredImageUrl($image->getImage(), 'product_small') : '';
    }

    /**
     * @param LineItem $lineItem
     * @return array
     */
    private function getVariantFieldsValuesForLineItem(LineItem $lineItem): array
    {
        $configurableProductsVariantFields = $this->configurableProductProvider
            ->getVariantFieldsValuesForLineItem($lineItem, true);

        return $configurableProductsVariantFields[$lineItem->getProduct()->getId()] ?? [];
    }

    /**
     * @param OrmResultAfter $event
     *
     * @return array
     */
    private function getLineItems(OrmResultAfter $event): array
    {
        $records = $event->getRecords();
        if (!$records) {
            return [];
        }

        $lineItemsIds = array_filter(
            array_merge(
                ...array_map(
                    function (ResultRecordInterface $record) {
                        return explode(',', $this->getRowId($record));
                    },
                    $records
                )
            )
        );

        if (!$lineItemsIds) {
            return [];
        }

        return $event
            ->getQuery()
            ->getEntityManager()
            ->getRepository(LineItem::class)
            ->findIndexedByIds($lineItemsIds);
    }
}
