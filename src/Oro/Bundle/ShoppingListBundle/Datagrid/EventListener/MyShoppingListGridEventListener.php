<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ConfigurableProductProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Populates line item records by required data.
 */
class MyShoppingListGridEventListener
{
    /** @var ManagerRegistry */
    private $registry;

    /** @var FrontendProductPricesDataProvider */
    private $productPricesDataProvider;

    /** @var ProductPriceFormatter */
    private $productPriceFormatter;

    /** @var ConfigurableProductProvider */
    private $configurableProductProvider;

    /** @var AttachmentManager */
    private $attachmentManager;

    /** @var ImagePlaceholderProviderInterface */
    private $imagePlaceholderProvider;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var NumberFormatter */
    private $numberFormatter;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param ManagerRegistry $registry
     * @param FrontendProductPricesDataProvider $productPricesDataProvider
     * @param ProductPriceFormatter $productPriceFormatter
     * @param ConfigurableProductProvider $configurableProductProvider
     * @param AttachmentManager $attachmentManager
     * @param ImagePlaceholderProviderInterface $imagePlaceholderProvider
     * @param UrlGeneratorInterface $urlGenerator
     * @param NumberFormatter $numberFormatter
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ManagerRegistry $registry,
        FrontendProductPricesDataProvider $productPricesDataProvider,
        ProductPriceFormatter $productPriceFormatter,
        ConfigurableProductProvider $configurableProductProvider,
        AttachmentManager $attachmentManager,
        ImagePlaceholderProviderInterface $imagePlaceholderProvider,
        UrlGeneratorInterface $urlGenerator,
        NumberFormatter $numberFormatter,
        TranslatorInterface $translator
    ) {
        $this->registry = $registry;
        $this->productPricesDataProvider = $productPricesDataProvider;
        $this->productPriceFormatter = $productPriceFormatter;
        $this->configurableProductProvider = $configurableProductProvider;
        $this->attachmentManager = $attachmentManager;
        $this->imagePlaceholderProvider = $imagePlaceholderProvider;
        $this->urlGenerator = $urlGenerator;
        $this->numberFormatter = $numberFormatter;
        $this->translator = $translator;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event): void
    {
        $shoppingListId = $event->getDatagrid()
            ->getParameters()
            ->get('shopping_list_id');

        $shoppingList = $this->registry->getManagerForClass(ShoppingList::class)
            ->getRepository(ShoppingList::class)
            ->find($shoppingListId);

        if (!$shoppingList) {
            return;
        }
        $translator = $this->translator;

        $lineItems = $shoppingList->getLineItems()->toArray();

        $productPrices = $this->productPricesDataProvider->getProductsAllPrices($lineItems);
        $matchedPrices = $this->productPricesDataProvider->getProductsMatchedPrice($lineItems);
        $allPrices = $this->productPriceFormatter->formatProducts($productPrices);
        $configurableProducts = $this->configurableProductProvider->getProducts($lineItems);

        $identifiedLineItems = [];
        foreach ($lineItems as $lineItem) {
            $identifiedLineItems[$lineItem->getId()] = $lineItem;
        }

        foreach ($event->getRecords() as $record) {
            /** @var LineItem[] $recordLineItems */
            $recordLineItems = array_filter(
                array_map(
                    static function (int $id) use ($identifiedLineItems) {
                        return $identifiedLineItems[$id] ?? null;
                    },
                    explode(',', $record->getValue('lineItemIds'))
                )
            );

            $isConfigurable = false;
            $product = null;
            $notes = null;
            $quantity = 0.0;
            $unit = null;
            $price = null;
            $allProductPrices = null;
            $subtotal = 0.0;
            $lineItemsData = [];
            $inventoryStatus = null;
            $imagePlaceholder = $this->imagePlaceholderProvider->getPath('product_small');

            foreach ($recordLineItems as $lineItem) {
                $productId = $lineItem->getProduct()->getId();
                $productAllPrices = $allPrices[$productId] ?? null;
                $quantity += $lineItem->getQuantity();
                $unit = $lineItem->getUnit();

                $isConfigurable = (bool) $lineItem->getParentProduct();
                if (!$isConfigurable) {
                    $allProductPrices = $productAllPrices;
                    $product = $lineItem->getProduct();
                    break;
                }

                $product = $lineItem->getParentProduct();

                /** @var Price $productMatchedPrice */
                $productMatchedPrice = $matchedPrices[$productId][$lineItem->getProductUnitCode()] ?? null;
                $lineItemSubtotal = $productMatchedPrice
                    ? $productMatchedPrice->getValue() * $lineItem->getQuantity()
                    : null;

                $itemData = [
                    'sku' => $lineItem->getProduct()->getSku(),
                    'productId' => $lineItem->getProduct()->getId(),
                    'productConfiguration' => array_map(
                        static function ($field) use ($translator) {
                            $field['label'] = $translator->trans($field['label']);
                            return $field;
                        },
                        $configurableProducts[$productId] ?? []
                    ),
                    'name' => $lineItem->getProduct()->getName(),
                    'note' => $lineItem->getNotes(),
                    'inventoryStatus_name' => $lineItem->getProduct()->getInventoryStatus()->getId(),
                    'inventoryStatus_label' => $lineItem->getProduct()->getInventoryStatus()->getName(),
                    'quantity' => $lineItem->getQuantity(),
                    'unit' => $lineItem->getProductUnit()->getCode(),
                    'price' => $productMatchedPrice ? $this->numberFormatter->formatCurrency($productMatchedPrice->getValue(), $productMatchedPrice->getCurrency()) : null,
                    'prices' => $productAllPrices,
                    'subtotal' => $productMatchedPrice ? $this->numberFormatter->formatCurrency($lineItemSubtotal, $productMatchedPrice->getCurrency()) : null,
                ];

                /** @var ProductImage $image */
                $image = $lineItem->getProduct()->getImagesByType('listing')->first();
                if ($image) {
                    $itemData['image'] = $this->attachmentManager->getFilteredImageUrl($image->getImage(), 'product_small');
                } else {
                    $itemData['imagePlaceholder'] = $imagePlaceholder;
                }
                $lineItemsData[] = $itemData;

                if ($subtotal !== null && $lineItemSubtotal !== null) {
                    $subtotal += $lineItemSubtotal;
                } else {
                    $subtotal = null;
                }
            }

            $record->setValue('subData', $isConfigurable ? $lineItemsData : null);
            $record->setValue('isConfigurable', $isConfigurable);
            $record->setValue('quantity', $quantity);
            $record->setValue('sku', $product->getSku());
            $record->setValue('productId', $product->getId());
            $record->setValue('name', $record->getValue('productName'));

            /** @var ProductImage $image */
            $image = $product->getImagesByType('listing')->first();
            if ($image) {
                $record->setValue(
                    'image',
                    $this->attachmentManager->getFilteredImageUrl($image->getImage(), 'product_small')
                );
            } else {
                $record->setValue('imagePlaceholder', $imagePlaceholder);
            }

            $record->setValue(
                'link',
                $this->urlGenerator->generate('oro_product_frontend_product_view', ['id' => $product->getId()])
            );

            $record->setValue('inventoryStatus_name', $product->getInventoryStatus()->getId());
            $record->setValue('inventoryStatus_label', $product->getInventoryStatus()->getName());
            $record->setValue('note', !$isConfigurable ? $lineItem->getNotes() : null);

            /** @var Price $price */
            $price = $matchedPrices[$product->getId()][$unit->getCode()] ?? null;
            if ($price) {
                $record->setValue(
                    'price',
                    $this->numberFormatter->formatCurrency($price->getValue(), $price->getCurrency())
                );
                $record->setValue(
                    'subtotal',
                    $this->numberFormatter->formatCurrency($price->getValue() * $quantity, $price->getCurrency())
                );
            } else {
                $record->setValue('price', null);
                $record->setValue('subtotal', $isConfigurable && $subtotal && $productMatchedPrice?
                    $this->numberFormatter->formatCurrency($subtotal, $productMatchedPrice->getCurrency()) : null);
            }

            $record->setValue('prices', $allProductPrices);
            $record->setValue('unit', $unit->getCode());
        }
    }
}
