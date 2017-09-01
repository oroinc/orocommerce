UPGRADE FROM 1.3 to 1.4
=======================

**IMPORTANT**
-------------

Some inline underscore templates from next bundles, were moved to separate .html file for each template:
 - PricingBundle
 - ProductBundle
 
Format of sluggable urls cache was changed, added support of localized slugs. Cache regeneration is required after update. 
 
CatalogBundle
-------------
- Class `Oro\Bundle\CatalogBundle\Provider\CategoryContextUrlProvider`
    - changed signature of `__construct` method. Dependency on `UserLocalizationManager` added. 
 
OrderBundle
-------------
- Form type `Oro\Bundle\OrderBundle\Tests\Unit\Form\Type\OrderDiscountItemsCollectionType` and related `oroorder/js/app/views/discount-items-view` JS view were removed, new `Oro\Bundle\OrderBundle\Form\Type\OrderDiscountCollectionTableType` and `oroorder/js/app/views/discount-collection-view` are introduced.
- Form type `Oro\Bundle\OrderBundle\Form\Type\OrderDiscountItemType` was changed for use in popup.

PromotionBundle
-------------
- Class `Oro\Bundle\PromotionBundle\Provider\DiscountRecalculationProvider` was removed
- Class `Oro\Bundle\PromotionBundle\Placeholder\OrderAdditionalPlaceholderFilter` was removed
- Class `Oro\Bundle\PromotionBundle\Provider\SubtotalProvider`
    - changed signature of `__construct` method. Sixth argument `Oro\Bundle\PromotionBundle\Provider\DiscountRecalculationProvider $discountRecalculationProvider` was removed
- Interface `Oro\Bundle\PromotionBundle\Discount\DiscountInterface` 
    - now is fluent, please make sure that all classes which implement it return `$this` for `setPromotion` and  `setMatchingProducts` methods
    - `getPromotion()` method return value type changed from `Oro\Bundle\PromotionBundle\Entity\Promotion` to `Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface`
    - `setPromotion()` method parameter's type changed from `Oro\Bundle\PromotionBundle\Entity\Promotion` to `Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface`
- Class `Oro\Bundle\PromotionBundle\Executor\PromotionExecutor`
    - changed signature of `__construct` method. Removed dependencies are first `Oro\Bundle\PromotionBundle\Provider\PromotionProvider $promotionProvider`,
     third `Oro\Bundle\PromotionBundle\DiscountDiscountFactory $discountFactory`,
     fifth `Oro\Bundle\PromotionBundle\Provider\MatchingProductsProvider $matchingProductsProvider`. Added dependency on newly added interface `Oro\Bundle\PromotionBundle\Provider\PromotionDiscountsProviderInterface $promotionDiscountsProvider`.

PaymentBundle
-------------
- Event `oro_payment.require_payment_redirect.PAYMENT_METHOD_IDENTIFIER` is no more specifically dispatched for each
payment method. Use generic `oro_payment.require_payment_redirect` event instead.
- Interface `Oro\Bundle\PaymentBundle\Context\Builder\PaymentContextBuilderInterface`
    - added `setWebsite()` method
- Interface `Oro\Bundle\PaymentBundle\Context\PaymentContextInterface`
    - added `getWebsite()` method

RedirectBundle
--------------
- Class `Oro\Bundle\RedirectBundle\Cache\UrlDataStorage`
    - changed signature of `setUrl` method. Optional integer parameter `$localizationId` added.
    - changed signature of `removeUrl` method. Optional integer parameter `$localizationId` added.
    - changed signature of `getUrl` method. Optional integer parameter `$localizationId` added.
    - changed signature of `getSlug` method. Optional integer parameter `$localizationId` added.
- Class `Oro\Bundle\RedirectBundle\Cache\UrlStorageCache`
    - changed signature of `__construct` method. Type of first argument changed from abstract class `FileCache` to interface `Cache`  
    - changed signature of `setUrl` method. Optional integer parameter `$localizationId` added.
    - changed signature of `removeUrl` method. Optional integer parameter `$localizationId` added.
    - changed signature of `getUrl` method. Optional integer parameter `$localizationId` added.
    - changed signature of `getSlug` method. Optional integer parameter `$localizationId` added.
- Class `Oro\Bundle\RedirectBundle\Routing\Router`
    - removed method `setFrontendHelper`, `setMatchedUrlDecisionMaker` added instead. `MatchedUrlDecisionMaker` should be used instead of FrontendHelper
    to check that current URL should be processed by Slugable Url matcher or generator
- Class `Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator`
    - changed signature of `__construct` method. Dependency on `UserLocalizationManager` added. 

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

PricingBundle
-------------
- Class `Oro\Bundle\PricingBundle\Entity\Repository\BaseProductPriceRepository` got an abstract method:
    - `getPriceListIdsByProduct(Product $product)` - that should return array of Price Lists identifiers witch contains price for given product
- Required option for layout block type 'product_prices' renamed from 'productUnitSelectionVisible' to 'isPriceUnitsVisible'

PayPalBundle
------------
- Class `Oro\Bundle\PayPalBundle\EventListener\Callback\RedirectListener`
    - changed signature of `__construct` method. Dependency on `PaymentMethodProviderInterface` added.

ProductBundle
------------

Enabled API for ProductImage and ProductImageType and added documentation of usage in Product API.

Product images and unit information for the grid are now part of the search index.
In order to see image changes, for example, immediate reindexation is required.     

- Class `Oro\Bundle\ProductBundle\EventListener\FrontendProductDatagridListener`
    - changed signature of `addProductImages` method. Removed the `$productIds` parameter.
    - changed signature of `addProductUnits` method. Removed the `$productIds` parameter.
    - dependency on `RegistryInterface` will soon be removed. `getProductRepository` and `getProductUnitRepository` flagged as deprecated.
- Class `Oro\Bundle\ProductBundle\EventListener\WebsiteSearchProductIndexerListener`
    - signature of `__construct` changed. Added dependencies: `RegistryInterface`, `AttachmentManager`    
- Class `Oro\Bundle\ProductBundle\Provider\ContentVariantContextUrlProvider`
    - changed signature of `__construct` method. Dependency on `UserLocalizationManager` added.

PromotionBundle
------------
- Class `Oro\Bundle\PromotionBundle\Discount\DiscountInterface`
    - changed signature of `apply` method. Changed type hinting to `DiscountContextInterface`.
- Class `Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyInterface`
    - changed signature of `process` method. Changed type hinting to `DiscountContextInterface`.
- Class `Oro\Bundle\PromotionBundle\Manager\AppliedDiscountManager`
    - renamed to `AppliedPromotionManager`
    - service of this manager renamed to `oro_promotion.applied_promotion_manager`
    - changed signature of `__construct` method
        - changed dependency from `ContainerInterface` to `ServiceLink`
        - added third argument of `AppliedPromotionMapper`.
    - renamed public method from `saveAppliedDiscounts` to `createAppliedPromotions`
    - removed public methods `removeAppliedDiscountByOrderLineItem` and `removeAppliedDiscountByOrder`
- Class `Oro\Bundle\PromotionBundle\EventListener\OrderLineItemAppliedDiscountsListener`
    - changed signature of `__construct` method
        - changed dependency from `DiscountsProvider` to `AppliedDiscountsProvider`.
- Class `Oro\Bundle\PromotionBundle\Form\Extension\OrderLineItemTypeExtension`
    - changed signature of `__construct` method
        - changed dependency from `DiscountsProvider` to `AppliedDiscountsProvider`.
- Class `Oro\Bundle\PromotionBundle\Form\Extension\OrderTypeExtension`
    - changed signature of `__construct` method
        - removed all dependencies
    - removed public method `onSubmit`.
- Class `Oro\Bundle\PromotionBundle\Provider\AppliedDiscountsProvider`
    - changed signature of `__construct` method
        - removed all dependencies
- Class `Oro\Bundle\PromotionBundle\Provider\DiscountsProvider`
    - removed with service definition.
