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
- Interface `Oro\Bundle\PaymentBundle\Context\Builder\PaymentContextBuilderInterface`
    - added `setWebsite()` method
- Interface `Oro\Bundle\PaymentBundle\Context\PaymentContextInterface`
    - added `getWebsite()` method

ShippingBundle
--------------
- Interface `Oro\Bundle\ShippingBundle\Context\Builder\ShippingContextBuilderInterface`
    - added `setWebsite()` method
- Interface `Oro\Bundle\ShippingBundle\Context\ShippingContextInterface`
    - added `getWebsite()` method

SaleBundle
----------
- Class `Oro\Bundle\SaleBundle\Entity\Quote`
    - now implements `Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface` (corresponding methods have been implemented before, thus it's just a formal change)

PromotionBundle
-------------
- Interface `Oro\Bundle\PromotionBundle\Discount\DiscountInterface` 
    - now is fluent, please make sure that all classes which implement it return `$this` for `setPromotion` and  `setMatchingProducts` methods
    - `getPromotion()` method return value type changed from `Oro\Bundle\PromotionBundle\Entity\Promotion` to `Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface`
    - `setPromotion()` method parameter's type changed from `Oro\Bundle\PromotionBundle\Entity\Promotion` to `Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface`
- Class `Oro\Bundle\PromotionBundle\Executor\PromotionExecutor`
    - changed signature of `__construct` method. Removed dependencies are first `Oro\Bundle\PromotionBundle\Provider\PromotionProvider $promotionProvider`,
     third `Oro\Bundle\PromotionBundle\DiscountDiscountFactory $discountFactory`,
     fifth `Oro\Bundle\PromotionBundle\Provider\MatchingProductsProvider $matchingProductsProvider`. Added dependency on newly added interface `Oro\Bundle\PromotionBundle\Provider\PromotionDiscountsProviderInterface $promotionDiscountsProvider`.
     


PricingBundle
-------------
- Class `Oro\Bundle\PricingBundle\Entity\Repository\BaseProductPriceRepository` got an abstract method:
    - `getPriceListIdsByProduct(Product $product)` - that should return array of Price Lists identifiers witch contains price for given product
- Required option for layout block type 'product_prices' renamed from 'productUnitSelectionVisible' to 'isPriceUnitsVisible'

PayPalBundle
------------
- Class `Oro\Bundle\PayPalBundle\EventListener\Callback\RedirectListener`
    - changed signature of `__construct` method. Dependency on `PaymentMethodProviderInterface` added.
