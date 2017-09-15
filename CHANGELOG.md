## 1.5.0 (Unreleased)

## 1.4.0 (2017-09-21)
[Show detailed list of changes](#file-incompatibilities-1-4-0.md)

### Added
* **PaymentBundle**: Interface `Oro\Bundle\PaymentBundle\Context\Builder\PaymentContextBuilderInterface`
    * added `setWebsite()` method
* **PaymentBundle**: Interface `Oro\Bundle\PaymentBundle\Context\PaymentContextInterface`
    * added `getWebsite()` method
* **ProductBundle**: Enabled API for ProductImage and ProductImageType and added documentation of usage in Product API.
* **ProductBundle**: Product images and unit information for the grid are now part of the search index. In order to see image changes, for example, immediate reindexation is required. 
* **PricingBundle**: Class `Oro\Bundle\PricingBundle\Entity\Repository\BaseProductPriceRepository` got an abstract method:
    * `getPriceListIdsByProduct(Product $product)` - that should return array of Price Lists identifiers witch contains price for given product
* **PricingBundle**: Api for `Oro\Bundle\PricingBundle\Entity\ProductPrice` entity was added. In sharding mode product prices can't be managed without `priceList` field, that's why in `get_list` action `priceList` filter is required and in all actions ID of entities has format `ProductPriceID-PriceListID`.
    * Class `Oro\Bundle\PricingBundle\Api\ProductPrice\Delete\PriceManagerDeleteHandler` was added to correctly remove prices in sharding mode
    * Interface `Oro\Bundle\PricingBundle\Api\ProductPrice\PriceListIDContextStorageInterface` was added to abstract the way of storing price list id in an api context
    * Class `Oro\Bundle\PricingBundle\Api\ProductPrice\PriceListIDInContextStorage` was added as a storage of price list id
    * Class `Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\EnableShardingOnConfigProcessor` was added to set sharding query hints on config and 'price_list_id = :price_list_id' condition on query
    * Class `Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\EnableShardingOnQueryProcessor` was added to set sharding query hints and 'price_list_id = :price_list_id' condition on query
    * Class `Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\LoadNormalizedProductPriceWithNormalizedIdProcessor` was added to normalize an output of update/create requests
    * Class `Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\NormalizeInputProductPriceIdProcessor` was added to transform id from request in 'guid-priceListId' format to 'guid' and save 'priceListId' to context
    * Class `Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\NormalizeOutputProductPriceIdProcessor` was added to normalize entity ids that are returned in response
    * Class `Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\SaveProductPriceProcessor` was added to correctly save price in sharding mode
    * Class `Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\StorePriceListInContextByFilterProcessor` was added to save priceListId from filter to context
    * Class `Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\StorePriceListInContextByProductPriceProcessor` was added to save priceListId from ProductPrice entity to context
    * Interface `Oro\Bundle\PricingBundle\Api\ProductPrice\ProductPriceIDByContextNormalizerInterface` was added to abstract the way of normalizing product price ids
    * Class `Oro\Component\ChainProcessor\ContextInterface\ProductPriceIDByPriceListIDNormalizer` was added to transform product price id to `ProductPriceID-PriceListID` format
    * Class `Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\ResetPriceRuleFieldOnUpdateProcessor` was added to reset product price rule when one of the fields: `value`, `quantity`, `unit`, `currency` changes
* **ShippingBundle**: Interface `Oro\Bundle\ShippingBundle\Context\Builder\ShippingContextBuilderInterface`
    * added `setWebsite()` method
* **ShippingBundle**: Interface `Oro\Bundle\ShippingBundle\Context\ShippingContextInterface`
    * added `getWebsite()` method

### Changed
* **RedirectBundle**: Format of sluggable urls cache was changed, added support of localized slugs.
* **RedirectBundle**: Class `Oro\Bundle\RedirectBundle\Cache\UrlDataStorage`
    * changed signature of `setUrl` method. Optional integer parameter `$localizationId` added.
    * changed signature of `removeUrl` method. Optional integer parameter `$localizationId` added.
    * changed signature of `getUrl` method. Optional integer parameter `$localizationId` added.
    * changed signature of `getSlug` method. Optional integer parameter `$localizationId` added.
* **RedirectBundle**: Class `Oro\Bundle\RedirectBundle\Cache\UrlStorageCache`
    * changed signature of `__construct` method. Type of first argument changed from abstract class `FileCache` to interface `Cache`  
    * changed signature of `setUrl` method. Optional integer parameter `$localizationId` added.
    * changed signature of `removeUrl` method. Optional integer parameter `$localizationId` added.
    * changed signature of `getUrl` method. Optional integer parameter `$localizationId` added.
    * changed signature of `getSlug` method. Optional integer parameter `$localizationId` added.
* **PricingBundle**: Some inline underscore templates were moved to separate .html file for each template.
* **PricingBundle**: Required option for layout block type 'product_prices' renamed from 'productUnitSelectionVisible' to 'isPriceUnitsVisible'
* **OrderBundle**:  Form type `Oro\Bundle\OrderBundle\Form\Type\OrderDiscountItemType` was changed for use in popup.
* **PromotionBundle**: Interface `Oro\Bundle\PromotionBundle\Discount\DiscountInterface` now is fluent, please make sure that all classes which implement it return `$this` for `setPromotion` and  `setMatchingProducts` methods
    * `getPromotion()` method return value type changed from `Oro\Bundle\PromotionBundle\Entity\Promotion` to `Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface`
    * `setPromotion()` method parameter's type changed from `Oro\Bundle\PromotionBundle\Entity\Promotion` to `Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface`
* **PromotionBundle**: Class `Oro\Bundle\PromotionBundle\Discount\DiscountInterface`
    * changed signature of `process` method. Changed type hinting to `DiscountContextInterface`.
* **PromotionBundle**: Class `Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyInterface`
    * changed signature of `apply` method. Changed type hinting to `DiscountContextInterface`.
* **PromotionBundle**: Class `Oro\Bundle\PromotionBundle\Manager\AppliedDiscountManager`
    * renamed to `AppliedPromotionManager`
    * service of this manager renamed to `oro_promotion.applied_promotion_manager`
    * renamed public method from `saveAppliedDiscounts` to `createAppliedPromotions`
    * removed public methods `removeAppliedDiscountByOrderLineItem` and `removeAppliedDiscountByOrder`
* **PaymentBundle**: Event `oro_payment.require_payment_redirect.PAYMENT_METHOD_IDENTIFIER` is no more specifically dispatched for each payment method. Use generic `oro_payment.require_payment_redirect` event instead.
* **RedirectBundle**: Class `Oro\Bundle\RedirectBundle\Routing\Router`
    * removed method `setFrontendHelper`, `setMatchedUrlDecisionMaker` added instead. `MatchedUrlDecisionMaker` should be used instead of FrontendHelper to check that current URL should be processed by Slugable Url matcher or generator
* **SaleBundle**: Class `Oro\Bundle\SaleBundle\Entity\Quote` now implements `Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface` (corresponding methods have been implemented before, thus it's just a formal change)
* **ProductBundle**: Some inline underscore templates were moved to separate .html file for each template.

### Deprecated
* **ProductBundle**: Class `Oro\Bundle\ProductBundle\EventListener\FrontendProductDatagridListener`
    * dependency on `RegistryInterface` will soon be removed. `getProductRepository` and `getProductUnitRepository` flagged as deprecated.

### Removed
* **OrderBundle**: Form type `Oro\Bundle\OrderBundle\Tests\Unit\Form\Type\OrderDiscountItemsCollectionType` and related `oroorder/js/app/views/discount-items-view` JS view were removed, new `Oro\Bundle\OrderBundle\Form\Type\OrderDiscountCollectionTableType` and `oroorder/js/app/views/discount-collection-view` are introduced.

## 1.3.6 (2017-09-11)
### Fixed

## 1.3.5 (2017-09-07)
### Fixed

## 1.3.4 (2017-09-04)
### Fixed

## 1.3.3 (2017-08-30)
### Fixed

## 1.3.2 (2017-08-22)
### Fixed

## 1.3.1 (2017-08-15)
### Fixed

## 1.3.0 LTS (2017-07-28)
[Show detailed list of changes](#file-incompatibilities-1-3-0.md)
### Added
### Changed
* **AuthorizeNetBundle**: AuthorizeNetBundle extracted to individual package. See [https://github.com/orocommerce/OroAuthorizeNetBundle](https://github.com/orocommerce/OroAuthorizeNetBundle) for details.

### Deprecated
### Removed
### Fixed

## 1.2.4 (2017-08-22)

## 1.2.0 (2017-06-01)
[Show detailed list of changes](#file-incompatibilities-1-2-0.md)

## 1.1.0 (2017-03-31)
[Show detailed list of changes](#file-incompatibilities-1-1-0.md)

## 1.0.14 (2017-08-22)

## 1.0.13 (2017-08-10)

## 1.0.2 (2017-03-21)

## 1.0.1 (2017-02-21)

## 1.0.0 (2017-01-18)