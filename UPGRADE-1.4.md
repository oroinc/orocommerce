UPGRADE FROM 1.3 to 1.4
=======================

**IMPORTANT**
-------------

Some inline underscore templates from next bundles, were moved to separate .html file for each template:
 - PricingBundle
 - ProductBundle

OrderBundle
-------------
- Form type `Oro\Bundle\OrderBundle\Tests\Unit\Form\Type\OrderDiscountItemsCollectionType` was removed, new `Oro\Bundle\OrderBundle\Form\Type\OrderDiscountCollectionTableType` is introduced, but it has different purpose and can't be used instead of the old one.
- Form type `Oro\Bundle\OrderBundle\Form\Type\OrderDiscountItemType` was refactored for use in popup, its template also was changed, therefore it can't be used instead of the old one.

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
