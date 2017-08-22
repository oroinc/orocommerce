UPGRADE FROM 1.3 to 1.4
=======================

**IMPORTANT**
-------------

Some inline underscore templates from next bundles, were moved to separate .html file for each template:
 - PricingBundle
 - ProductBundle

OrderBundle
-------------
- Form type `Oro\Bundle\OrderBundle\Tests\Unit\Form\Type\OrderDiscountItemsCollectionType` was removed, new `Oro\Bundle\OrderBundle\Form\Type\OrderDiscountCollectionTableType` is introduced.
- Form type `Oro\Bundle\OrderBundle\Form\Type\OrderDiscountItemType` was changed for use in popup.

PromotionBundle
-------------
- Class `Oro\Bundle\PromotionBundle\Provider\DiscountRecalculationProvider` was removed
- Class `Oro\Bundle\PromotionBundle\Placeholder\OrderAdditionalPlaceholderFilter` was removed
- Class `Oro\Bundle\PromotionBundle\Provider\SubtotalProvider`
    - changed signature of `__construct` method. Sixth argument `Oro\Bundle\PromotionBundle\Provider\DiscountRecalculationProvider $discountRecalculationProvider` was removed

PaymentBundle
-------------
- Event `oro_payment.require_payment_redirect.PAYMENT_METHOD_IDENTIFIER` is no more specifically dispatched for each
payment method. Use generic `oro_payment.require_payment_redirect` event instead.

PricingBundle
-------------
- Class `Oro\Bundle\PricingBundle\Entity\Repository\BaseProductPriceRepository` got an abstract method:
    - `getPriceListIdsByProduct(Product $product)` - that should return array of Price Lists identifiers witch contains price for given product
- Required option for layout block type 'product_prices' renamed from 'productUnitSelectionVisible' to 'isPriceUnitsVisible'

PayPalBundle
------------
- Class `Oro\Bundle\PayPalBundle\EventListener\Callback\RedirectListener`
    - changed signature of `__construct` method. Dependency on `PaymentMethodProviderInterface` added.
