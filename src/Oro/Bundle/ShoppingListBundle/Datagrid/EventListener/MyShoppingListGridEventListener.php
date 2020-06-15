<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\EventListener;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface as Record;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ConfigurableProductProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Event\LineItemDataEvent;
use Oro\Bundle\ShoppingListBundle\Validator\LineItemViolationsProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Populates line item records by required data.
 */
class MyShoppingListGridEventListener
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var TranslatorInterface */
    private $translator;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var NumberFormatter */
    private $numberFormatter;

    /** @var AttachmentManager */
    private $attachmentManager;

    /** @var FrontendProductPricesDataProvider */
    private $productPricesDataProvider;

    /** @var ConfigurableProductProvider */
    private $configurableProductProvider;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var LineItemViolationsProvider */
    private $violationsProvider;

    /** @var array */
    private $configurableProductLabels = [];

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param TranslatorInterface $translator
     * @param EventDispatcherInterface $eventDispatcher
     * @param NumberFormatter $numberFormatter
     * @param AttachmentManager $attachmentManager
     * @param FrontendProductPricesDataProvider $productPricesDataProvider
     * @param ConfigurableProductProvider $configurableProductProvider
     * @param LocalizationHelper $localizationHelper
     * @param LineItemViolationsProvider $violationsProvider
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator,
        EventDispatcherInterface $eventDispatcher,
        NumberFormatter $numberFormatter,
        AttachmentManager $attachmentManager,
        FrontendProductPricesDataProvider $productPricesDataProvider,
        ConfigurableProductProvider $configurableProductProvider,
        LocalizationHelper $localizationHelper,
        LineItemViolationsProvider $violationsProvider
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->eventDispatcher = $eventDispatcher;
        $this->numberFormatter = $numberFormatter;
        $this->attachmentManager = $attachmentManager;
        $this->productPricesDataProvider = $productPricesDataProvider;
        $this->configurableProductProvider = $configurableProductProvider;
        $this->localizationHelper = $localizationHelper;
        $this->violationsProvider = $violationsProvider;
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

        $matchedPrices = $this->productPricesDataProvider->getProductsMatchedPrice($lineItems->toArray());
        $errors = $this->violationsProvider->getLineItemErrors($lineItems);
        $identifiedLineItems = $this->getIdentifiedLineItems($lineItems);

        foreach ($event->getRecords() as $record) {
            /** @var LineItem[] $recordLineItems */
            $recordLineItems = array_intersect_key(
                $identifiedLineItems,
                array_flip(explode(',', $record->getValue('lineItemIds')))
            );

            if ($record->getValue('isConfigurable')) {
                $this->processConfigurableProduct($record, $recordLineItems, $matchedPrices, $errors);
            } else {
                $this->processSimpleProduct($record, reset($recordLineItems), $matchedPrices, $errors);
            }
        }
    }

    /**
     * @param Record $record
     * @param LineItem $item
     * @param array $prices
     * @param array $errors
     */
    private function processSimpleProduct(Record $record, LineItem $item, array $prices, array $errors): void
    {
        $product = $item->getProduct();

        $productId = $product->getId();
        $record->setValue('productId', $productId);

        $qty = $item->getQuantity();
        $record->setValue('quantity', $qty);

        $unit = $item->getProductUnitCode();
        $record->setValue('unit', $unit);

        $status = $product->getInventoryStatus();
        $record->setValue('inventoryStatus', ['name' => $status->getId(), 'label' => $status->getName()]);

        $record->setValue('name', $this->localizationHelper->getLocalizedValue($product->getNames()));
        $record->setValue('note', $item->getNotes());
        $record->setValue(
            'link',
            $this->urlGenerator->generate('oro_product_frontend_product_view', ['id' => $productId])
        );

        /** @var Price $productPrice */
        $productPrice = $prices[$productId][$unit] ?? null;
        if ($productPrice) {
            $price = $productPrice->getValue();
            $currency = $productPrice->getCurrency();

            $record->setValue('price', $this->numberFormatter->formatCurrency($price, $currency));
            $record->setValue('subtotal', $this->numberFormatter->formatCurrency($price * $qty, $currency));
        }

        /** @var ProductImage $image */
        $image = $product->getImagesByType('listing')->first();
        if ($image) {
            $record->setValue(
                'image',
                $this->attachmentManager->getFilteredImageUrl($image->getImage(), 'product_small')
            );
        }

        $event = new LineItemDataEvent([$item]);
        $this->eventDispatcher->dispatch($event, LineItemDataEvent::NAME);

        foreach ($event->getDataForLineItem($item->getId()) as $name => $value) {
            $record->setValue($name, $value);
        }

        $record->setValue('errors', $this->getErrors($errors, $product->getSku(), $unit));
    }

    /**
     * @param Record $record
     * @param LineItem[] $items
     * @param array $prices
     * @param array $errors
     */
    private function processConfigurableProduct(Record $record, array $items, array $prices, array $errors): void
    {
        $rowQuantity = 0.0;
        $rowSubtotal = 0.0;
        $rowCurrency = null;
        $data = [];

        $event = new LineItemDataEvent($items);
        $this->eventDispatcher->dispatch($event, LineItemDataEvent::NAME);

        foreach ($items as $item) {
            $product = $item->getProduct();
            $quantity = $item->getQuantity();
            $unit = $item->getProductUnitCode();

            $productId = $product->getId();
            $productStatus = $product->getInventoryStatus();

            $itemData = [
                'productId' => $productId,
                'sku' => $product->getSku(),
                'quantity' => $quantity,
                'unit' => $unit,
                'inventoryStatus' => ['name' => $productStatus->getId(), 'label' => $productStatus->getName()],
                'note' => $item->getNotes(),
                'price' => null,
                'subtotal' => null,
                'productConfiguration' => $this->getConfigurableProducts($item),
                'errors' => $this->getErrors($errors, $product->getSku(), $unit),
            ];

            /** @var Price $productPrice */
            $productPrice = $prices[$productId][$unit] ?? null;
            if ($productPrice) {
                $price = $productPrice->getValue();
                $currency = $productPrice->getCurrency();

                $subtotal = $price * $quantity;

                $itemData['price'] = $this->numberFormatter->formatCurrency($price, $currency);
                $itemData['subtotal'] = $this->numberFormatter->formatCurrency($subtotal, $currency);

                if ($rowSubtotal !== null) {
                    $rowSubtotal += $subtotal;
                    $rowCurrency = $currency;
                }
            } else {
                $rowSubtotal = null;
            }

            $rowQuantity += $quantity;

            /** @var ProductImage $image */
            $image = $product->getImagesByType('listing')->first();
            if ($image) {
                $itemData['image'] = $this->attachmentManager->getFilteredImageUrl(
                    $image->getImage(),
                    'product_small'
                );
            }

            foreach ($event->getDataForLineItem($item->getId()) as $name => $value) {
                $itemData[$name] = $value;
            }

            $data[] = $itemData;
        }

        $record->setValue('subData', $data);
        $record->setValue('quantity', $rowQuantity);
        if ($rowSubtotal) {
            $record->setValue('subtotal', $this->numberFormatter->formatCurrency($rowSubtotal, $rowCurrency));
        }

        $lineItem = reset($items);
        $product = $lineItem->getParentProduct();

        $record->setValue('productId', $product->getId());
        $record->setValue('name', $this->localizationHelper->getLocalizedValue($product->getNames()));
        $record->setValue('unit', $lineItem->getProductUnitCode());
        $record->setValue(
            'link',
            $this->urlGenerator->generate('oro_product_frontend_product_view', ['id' => $product->getId()])
        );

        /** @var ProductImage $image */
        $image = $product->getImagesByType('listing')->first();
        if ($image) {
            $record->setValue(
                'image',
                $this->attachmentManager->getFilteredImageUrl($image->getImage(), 'product_small')
            );
        }
    }

    /**
     * @param LineItem $item
     * @return array
     */
    private function getConfigurableProducts(LineItem $item): array
    {
        $configurableProducts = $this->configurableProductProvider->getLineItemProduct($item);
        $configurableProducts = $configurableProducts[$item->getProduct()->getId()] ?? null;
        if (!$configurableProducts) {
            return [];
        }

        foreach ($configurableProducts as &$configurableProduct) {
            $label = $configurableProduct['label'];
            if (!isset($this->configurableProductLabels[$label])) {
                $this->configurableProductLabels[$label] = $this->translator->trans($label);
            }

            $configurableProduct['label'] = $this->configurableProductLabels[$label];
        }
        unset($configurableProduct);

        return $configurableProducts;
    }

    /**
     * @param array $errors
     * @param string $sku
     * @param string $unit
     * @return array
     */
    private function getErrors(array $errors, string $sku, string $unit): array
    {
        return array_map(
            static function (ConstraintViolationInterface $error) {
                return $error->getMessage();
            },
            $errors[sprintf('product.%s.%s', $sku, $unit)] ?? []
        );
    }

    /**
     * @param OrmResultAfter $event
     *
     * @return Collection|null
     */
    private function getLineItems(OrmResultAfter $event): ?Collection
    {
        $shoppingListId = $event->getDatagrid()
            ->getParameters()
            ->get('shopping_list_id');

        if (!$shoppingListId) {
            return null;
        }

        $shoppingList = $event->getQuery()
            ->getEntityManager()
            ->getRepository(ShoppingList::class)
            ->find($shoppingListId);

        if (!$shoppingList) {
            return null;
        }

        return $shoppingList->getLineItems();
    }

    /**
     * @param Collection $lineItems
     *
     * @return array
     */
    private function getIdentifiedLineItems(Collection $lineItems): array
    {
        $identifiedLineItems = [];
        foreach ($lineItems as $lineItem) {
            $identifiedLineItems[$lineItem->getId()] = $lineItem;
        }

        return $identifiedLineItems;
    }
}
