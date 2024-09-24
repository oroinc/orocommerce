<?php

namespace Oro\Bundle\CheckoutBundle\Twig;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ConfigurableProductProvider;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to retrieve order data for checkout process,
 * including all products with amounts and subtotals:
 *   - order_line_items
 */
class LineItemsExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    private function getTotalsProvider(): TotalProcessorProvider
    {
        return $this->container->get(TotalProcessorProvider::class);
    }

    private function getLineItemSubtotalProvider(): LineItemSubtotalProvider
    {
        return $this->container->get(LineItemSubtotalProvider::class);
    }

    private function getLocalizationHelper(): LocalizationHelper
    {
        return $this->container->get(LocalizationHelper::class);
    }

    private function getEntityNameResolver(): EntityNameResolver
    {
        return $this->container->get(EntityNameResolver::class);
    }

    private function getConfigurableProductProvider(): ConfigurableProductProvider
    {
        return $this->container->get('oro_product.layout.data_provider.configurable_products');
    }

    private function getProductName(?Product $product): ?string
    {
        return $this->getEntityNameResolver()->getName(
            $product,
            EntityNameProviderInterface::FULL,
            $this->getLocalizationHelper()->getCurrentLocalization()
        );
    }

    #[\Override]
    public function getFunctions()
    {
        return [new TwigFunction('order_line_items', [$this, 'getOrderLineItems'])];
    }

    public function getOrderLineItems(Order $order): array
    {
        $lineItems = [];
        foreach ($order->getLineItems() as $lineItem) {
            $data['product_name'] = $this->getProductName($lineItem->getProduct()) ?? $lineItem->getFreeFormProduct();
            $data['seller_name'] = $lineItem->getProduct()?->getOrganization()->getName();
            $data['product_sku'] = $lineItem->getProductSku();
            $data['quantity'] = $lineItem->getQuantity();
            $data['unit'] = $lineItem->getProductUnit();
            $data['price'] = $lineItem->getPrice();
            $data['comment'] = $lineItem->getComment();
            $data['ship_by'] = $lineItem->getShipBy();
            $data['id'] = $lineItem->getEntityIdentifier();
            $data['subtotal'] = Price::create(
                $this->getLineItemSubtotalProvider()->getRowTotal($lineItem, $order->getCurrency()),
                $order->getCurrency()
            );
            $variantFieldsValues = $this->getConfigurableProductProvider()
                ->getVariantFieldsValuesForLineItem($lineItem, true);
            $data['variant_fields_values'] = reset($variantFieldsValues) ?: [];
            $data['kitItemLineItems'] = $this->getKitItemLineItemsData($lineItem);

            $lineItems[] = $data;
        }
        $result['lineItems'] = $lineItems;

        $result['subtotals'] = $this->getSubtotals($order);
        $result['total'] = $this->getTotal($order);

        return $result;
    }

    protected function getKitItemLineItemsData(OrderLineItem $lineItem): array
    {
        $kitItemLineItemsData = [];
        foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
            $kitItemLineItemData['kitItemLabel'] = $this->getLocalizationHelper()->getLocalizedValue(
                $kitItemLineItem->getKitItem()->getLabels()
            );
            $kitItemLineItemData['unit'] = $kitItemLineItem->getProductUnit();
            $kitItemLineItemData['quantity'] = $kitItemLineItem->getQuantity();
            $kitItemLineItemData['price'] = $kitItemLineItem->getPrice();
            $kitItemLineItemData['productName'] = $this->getProductName($kitItemLineItem->getProduct());
            $kitItemLineItemData['productSku'] = $kitItemLineItem->getProductSku();

            $variantFieldsValues = $this->getConfigurableProductProvider()
                ->getVariantFieldsValuesForLineItem($kitItemLineItem, true);
            $kitItemLineItemData['variant_fields_values'] = reset($variantFieldsValues) ?: [];

            $kitItemLineItemsData[] = $kitItemLineItemData;
        }

        return $kitItemLineItemsData;
    }

    protected function getSubtotals(Order $order): array
    {
        $result = [];
        $subtotals = $this->getTotalsProvider()->getSubtotals($order);
        foreach ($subtotals as $subtotal) {
            $result[] = [
                'label' => $subtotal->getLabel(),
                'totalPrice' => Price::create(
                    $subtotal->getSignedAmount(),
                    $subtotal->getCurrency()
                )
            ];
        }

        return $result;
    }

    protected function getTotal(Order $order): array
    {
        $total = $this->getTotalsProvider()->getTotal($order);

        return [
            'label' => $total->getLabel(),
            'totalPrice' => $total->getTotalPrice()
        ];
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            TotalProcessorProvider::class,
            LineItemSubtotalProvider::class,
            LocalizationHelper::class,
            EntityNameResolver::class,
            'oro_product.layout.data_provider.configurable_products' => ConfigurableProductProvider::class
        ];
    }
}
