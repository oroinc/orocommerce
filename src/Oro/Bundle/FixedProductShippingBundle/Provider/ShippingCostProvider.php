<?php

namespace Oro\Bundle\FixedProductShippingBundle\Provider;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\FixedProductShippingBundle\Migrations\Data\ORM\LoadPriceAttributePriceListData;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\PriceAttributePricesProvider;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingKitItemLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

/**
 * Calculate shipping cost value for 'Fixed Product Shipping' integration.
 */
class ShippingCostProvider
{
    public const DEFAULT_COST = 0.0;

    private PriceAttributePricesProvider $pricesProvider;
    private ManagerRegistry $registry;
    private ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory;
    private ProductPriceProviderInterface $productPriceProvider;

    private ?PriceAttributePriceList $priceList = null;

    public function __construct(PriceAttributePricesProvider $pricesProvider, ManagerRegistry $registry)
    {
        $this->pricesProvider = $pricesProvider;
        $this->registry = $registry;
    }

    public function setPriceScopeCriteriaFactory(
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory
    ): void {
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
    }

    public function setProductPriceProvider(ProductPriceProviderInterface $productPriceProvider): void
    {
        $this->productPriceProvider = $productPriceProvider;
    }

    /**
     * @param Collection<ShippingLineItem|ShippingKitItemLineItem> $lineItems
     * @param string $currency
     * @return float
     * @throws MathException
     */
    public function getCalculatedProductShippingCost(
        ShippingLineItemCollectionInterface $lineItems,
        string $currency
    ): float {
        $this->ensurePriceListShippingCostAttributeLoaded();

        if (!$this->priceList) {
            return self::DEFAULT_COST;
        }

        $shipping = BigDecimal::of(self::DEFAULT_COST);

        foreach ($lineItems as $lineItem) {
            /** @var Product $product */
            $product = $lineItem->getProduct();
            if (!$product) {
                continue;
            }

            $shipping = $shipping->plus($this->getItemShippingPrice($lineItem, $currency));
        }

        return $shipping->toFloat();
    }

    /**
     * @param object $checkout
     * @param Collection<ShippingLineItem|ShippingKitItemLineItem> $lineItems
     * @param string $currency
     * @return BigDecimal[]
     * @throws MathException
     */
    public function getCalculatedProductShippingCostWithSubtotal(
        object $checkout,
        Collection $lineItems,
        string $currency
    ): array {
        $this->ensurePriceListShippingCostAttributeLoaded();

        if (!$this->priceList || !$checkout instanceof Checkout) {
            return [BigDecimal::of(self::DEFAULT_COST), BigDecimal::of(self::DEFAULT_COST)];
        }

        return $this->calculateCost($checkout, $lineItems, $currency);
    }

    /**
     * @param Checkout $checkout
     * @param Collection<ShippingLineItem|ShippingKitItemLineItem> $lineItems
     * @param string $currency
     * @return BigDecimal[]
     * @throws MathException
     */
    private function calculateCost(
        Checkout $checkout,
        Collection $lineItems,
        string $currency
    ): array {
        $sumSubtotal = BigDecimal::of(self::DEFAULT_COST);
        $sumShipping = BigDecimal::of(self::DEFAULT_COST);
        $shippingCostWithSubtotal = [$sumSubtotal, $sumShipping];
        /** @var ShippingLineItem|ShippingKitItemLineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            /** @var Product $product */
            $product = $lineItem->getProduct();
            if (!$product) {
                continue;
            }

            [$subtotal, $shipping] = $this->getItemPrice($checkout, $lineItem, $currency);
            $sumSubtotal = $sumSubtotal->plus($subtotal);
            $sumShipping = $sumShipping->plus($shipping);

            $shippingCostWithSubtotal = [$sumSubtotal, $sumShipping];
        }

        return $shippingCostWithSubtotal;
    }

    /**
     * @param ShippingLineItem|ShippingKitItemLineItem $lineItem
     * @param string $currency
     * @return BigDecimal
     * @throws MathException
     */
    private function getItemShippingPrice(
        ShippingLineItem|ShippingKitItemLineItem $lineItem,
        string $currency
    ): BigDecimal {
        $this->ensurePriceListShippingCostAttributeLoaded();

        $product = $lineItem->getProduct();
        $attribute = $this->pricesProvider->getPricesWithUnitAndCurrencies($this->priceList, $product);
        $unitCode = $lineItem->getProductUnitCode();

        if (isset($attribute[$unitCode][$currency])) {
            $shippingPrice = $attribute[$unitCode][$currency]->getPrice()?->getValue() ?? self::DEFAULT_COST;
        } else {
            $shippingPrice = self::DEFAULT_COST;
        }

        return BigDecimal::of($shippingPrice);
    }

    /**
     * @param Checkout $checkout
     * @param ShippingLineItem|ShippingKitItemLineItem $lineItem
     * @param string $currency
     * @return BigDecimal[]
     * @throws MathException
     */
    private function getItemPrice(
        Checkout $checkout,
        ShippingLineItem|ShippingKitItemLineItem $lineItem,
        string $currency
    ): array {
        $product = $lineItem->getProduct();

        if ($product->isKit()) {
            $subtotalWithShipping = match ($product->getKitShippingCalculationMethod()) {
                Product::KIT_SHIPPING_ALL =>
                $this->getKitAndItemsPriceWithShipping($checkout, $lineItem, $currency),
                Product::KIT_SHIPPING_ONLY_ITEMS =>
                $this->getKitItemsPriceWithShipping($lineItem, $currency),
                Product::KIT_SHIPPING_ONLY_PRODUCT =>
                $this->getKitRealPriceWithShipping($checkout, $lineItem, $currency),
                default => $this->getKitAndItemsPriceWithShipping($checkout, $lineItem, $currency),
            };
        } else {
            $subtotalWithShipping = $this->getLineItemProductPrice($lineItem, $currency);
        }

        return $subtotalWithShipping;
    }

    /**
     * @param ShippingLineItem|ShippingKitItemLineItem $lineItem
     * @param string $currency
     * @return BigDecimal[]
     * @throws MathException
     */
    private function getLineItemProductPrice(
        ShippingLineItem|ShippingKitItemLineItem $lineItem,
        string $currency
    ): array {
        $subtotal = BigDecimal::of($lineItem->getPrice()?->getValue() ?? self::DEFAULT_COST)
            ->multipliedBy($lineItem->getQuantity());
        $shipping = BigDecimal::of($this->getItemShippingPrice($lineItem, $currency))
            ->multipliedBy($lineItem->getQuantity());

        return [$subtotal, $shipping];
    }

    /**
     * @param Checkout $checkout
     * @param ShippingLineItem $lineItem
     * @param string $currency
     * @return BigDecimal[]
     * @throws MathException
     */
    private function getKitAndItemsPriceWithShipping(
        Checkout $checkout,
        ShippingLineItem $lineItem,
        string $currency
    ): array {
        $subtotal = BigDecimal::of(self::DEFAULT_COST);
        $shipping = BigDecimal::of(self::DEFAULT_COST);
        [$kitSubtotal, $kitShipping] = $this->getKitRealPriceWithShipping($checkout, $lineItem, $currency);
        [$kitLineItemsSubtotal, $kitLineItemsShipping] = $this->getKitItemsPriceWithShipping($lineItem, $currency);
        $subtotal = $subtotal->plus($kitSubtotal)->plus($kitLineItemsSubtotal);
        $shipping = $shipping->plus($kitShipping)->plus($kitLineItemsShipping);

        return [$subtotal, $shipping];
    }

    /**
     * @param Checkout $checkout
     * @param ShippingLineItem $lineItem
     * @param string $currency
     * @return BigDecimal[]
     * @throws MathException
     */
    private function getKitRealPriceWithShipping(
        Checkout $checkout,
        ShippingLineItem $lineItem,
        string $currency
    ): array {
        $product = $lineItem->getProduct();

        $priceScopeCriteria = $this
            ->priceScopeCriteriaFactory
            ->createByContext($checkout);
        $matchedKitPrices = $this
            ->productPriceProvider
            ->getPricesByScopeCriteriaAndProducts($priceScopeCriteria, [$product], [$currency]);
        $kitPrice = $this->getKitPrice($matchedKitPrices, $product->getId());
        $kitShippingPrice = $this->getItemShippingPrice($lineItem, $currency);
        $subtotal = BigDecimal::of($kitPrice)
            ->multipliedBy($lineItem->getQuantity());
        $shipping = BigDecimal::of($kitShippingPrice)
            ->multipliedBy($lineItem->getQuantity());

        return [$subtotal, $shipping];
    }

    private function getKitPrice(array $matchedKitPrices, int $productId): float
    {
        if (!array_key_exists($productId, $matchedKitPrices)) {
            return self::DEFAULT_COST;
        }

        $kitPriceDto = current($matchedKitPrices[$productId]);

        return $kitPriceDto->getPrice()?->getValue() ?? self::DEFAULT_COST;
    }

    /**
     * @param ShippingLineItem $lineItem
     * @param string $currency
     * @return BigDecimal[]
     * @throws MathException
     */
    private function getKitItemsPriceWithShipping(ShippingLineItem $lineItem, string $currency): array
    {
        $kitLineItems = $lineItem->getKitItemLineItems();

        $subtotal = BigDecimal::of(self::DEFAULT_COST);
        $shipping = BigDecimal::of(self::DEFAULT_COST);

        foreach ($kitLineItems as $kitLineItem) {
            [$kitItemSubtotal, $kitItemShipping] = $this->getLineItemProductPrice($kitLineItem, $currency);
            $subtotal = $subtotal->plus($kitItemSubtotal->multipliedBy($lineItem->getQuantity()));
            $shipping = $shipping->plus($kitItemShipping->multipliedBy($lineItem->getQuantity()));
        }

        return [$subtotal, $shipping];
    }

    private function ensurePriceListShippingCostAttributeLoaded(): void
    {
        if ($this->priceList === null) {
            $this->priceList = $this->getPriceListShippingCostAttribute();
        }
    }

    private function getPriceListShippingCostAttribute(): ?PriceAttributePriceList
    {
        return $this->registry
            ->getRepository(PriceAttributePriceList::class)
            ->findOneBy(['name' => LoadPriceAttributePriceListData::SHIPPING_COST_NAME], ['id' => 'DESC']);
    }
}
