## 3.1.0

### Changed
#### ShoppingListBundle
* Functionality related to the currently active shopping list was moved from `Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager` to `Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager`. The service id for the CurrentShoppingListManager is `oro_shopping_list.manager.current_shopping_list`.
* Service `oro_shopping_list.shopping_list.manager` was renamed to `oro_shopping_list.manager.shopping_list`.

## 3.0.0 (2018-07-27)
[Show detailed list of changes](incompatibilities-3-0.md)

## 3.0.0-rc (2018-05-31)
[Show detailed list of changes](incompatibilities-3-0-rc.md)

## 3.0.0-beta (2018-03-30)
[Show detailed list of changes](incompatibilities-3-0-beta.md)

### Changed
#### ElasticSearchBundle
* Method `validateReindexRequest` at `Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator` was renamed to `validateRequestParameters`

### Added
#### ProductBundle
* Added a listener to the `oro_product.display_simple_variations` config field that cleans the product and category layout cache when changes occur.

### Removed
#### ProductBundle
* Removed listener `oro_product.event_listener.restrict.display_product_variations`. The service `oro_product.config.event_listener.display_simple_variations_listener` is used instead.
* Removed listener `oro_product.event_listener.datagrid.frontend_product_search.display_product_variations`. The service  `oro_product.config.event_listener.display_simple_variations_listener` is used instead.

## 1.6.0 (2018-01-31)
[Show detailed list of changes](incompatibilities-1-6.md)

### Added
#### CatalogBundle
* Improved caching of home page, added `Oro\Component\Cache\Layout\DataProviderCacheTrait` to the following layout data providers:
    * `Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoriesProductsProvider` (`=data["featured_categories"].getAll()`) 
    * `Oro\Bundle\CatalogBundle\Layout\DataProvider\FeaturedCategoriesProvider` (`=data["categories_products"].getCountByCategories()`)

#### PricingBundle
* Improved security of pricing rules cache, added hash to stored data to track consistency. Old caches will be recalculated automatically.
* Class `Oro\Bundle\PricingBundle\Cache\RuleCache`
    * method `__construct` added dependency on `Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface`

#### ProductBundle
* Class `Oro\Bundle\CatalogBundle\Model\ExtendProduct`:
    * method `setCategory` was added
    * method `getCategory` was added
    * property `category_id` was added
* Improved security of segment products provider cache, added hash to stored data to track consistency. Old caches should me removed as inconsistent.
* Class `Oro\Bundle\ProductBundle\Layout\DataProvider\AbstractSegmentProductsProvider`
    * method `__construct` added dependency on `Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface`

### Changed
#### AlternativeCheckoutBundle
* Operation `oro_accept_quote` renamed to `oro_sale_accept_quote` and moved to `SaleBundle`

#### CatalogBundle
* Layout data provider method `=data["featured_categories"].getAll()` returns data in format `[['id' => %d, 'title' => %s, 'small_image' => %s], [...], ...]`
* Relation between Category and Product has been changed from ManyToMany unidirectional with joining table to ManyToOne bidirectional.
* Class `Oro\Bundle\CatalogBundle\Entity\Category`:
    * method `setProducts` was moved to `Oro\Bundle\CatalogBundle\Model\ExtendCategory` 
    * method `getProducts` was moved to `Oro\Bundle\CatalogBundle\Model\ExtendCategory` 
    * method `addProduct` was moved to `Oro\Bundle\CatalogBundle\Model\ExtendCategory` 
    * method `removeProducts` was moved to `Oro\Bundle\CatalogBundle\Model\ExtendCategory`
    * property `products` was moved to `Oro\Bundle\CatalogBundle\Model\ExtendCategory`

#### CheckoutBundle
* Operation `oro_checkout_frontend_quote_submit_to_order` renamed to `oro_sale_frontend_quote_submit_to_order` and moved to `SaleBundle`

#### TaxBundle
* Now enabled tax provider in system config is a main point for tax calculation instead of TaxManager (look at the TaxProviderInterface). Read more in documentation [how to setup custom tax provider](./src/Oro/Bundle/TaxBundle/README.md#create-custom-tax-provider).

### Deprecated
#### CatalogBundle
* The `CategoryRepository::getCategoriesProductsCountQueryBuilder` is deprecated. Not using.

### Removed
#### CatalogBundle
* Removed `oro_category_to_product` joining table.

## 1.5.0 (2017-11-30)
[Show detailed list of changes](incompatibilities-1-5.md)

### Added
#### CheckoutBundle
* Added `CheckoutLineItem` and `CheckoutSubtotal` entities. They will be used in `Checkout` entity to store data. Previously for these purposes used line items and subtotals of Checkout source entity (`ShoppingList` or `QuoteDemand` entities).
#### OrderBundle
* Added Previously purchased products functionality. [Documentation](./src/Oro/Bundle/OrderBundle/Resources/doc/previously-purchased-products.md)
#### RFPBundle
* Added new email template `request_create_confirmation`. It will be send when guest customer user create new request for quote.
* Added new twig function `rfp_products` that returns list of request products (formatted) for current request for quote. Can be used in email templates.
#### WebsiteSearchBundle
* Added interface `Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchableAttributeTypeInterface` that should be implemented in case new type of arguments added.

#### RedirectBundle
* Added interface `Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface` that should be implemented by URL cache services.
* Added interface `Oro\Bundle\RedirectBundle\Provider\SluggableUrlProviderInterface` that should be implemented by URL providers.
* Added new URL caches: `key_value` and `local`. Previous implementation was registered with `storage` key and was set by default.
* Added Sluggable URL providers which are used by URL generator. This service encapsulate logic related to semantic URL retrieval.
Was added 2 provider implementations: `database` and `cache`. `database` is set by default.
* Added DI parameter `oro_redirect.url_cache_type` for URL cache configuration
* Added DI parameter `oro_redirect.url_provider_type` for URL provider configuration
* Added DI parameter `oro_redirect.url_storage_cache.split_deep` for tuning `storage` cache

### Changed
#### CheckoutBundle
* Entity `Oro\Bundle\CheckoutBundle\Entity\Checkout`:
    * no longer implements `Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface`;
    * implements `Oro\Bundle\CurrencyBundle\Entity\CurrencyAwareInterface`.

#### InventoryBundle
* Added Low Inventory Highlights functionality.[Documentation](./src/Oro/Bundle/InventoryBundle/Resources/doc/low_inventory_highlights.md)

#### ProductBundle
* Updated website search configuration file `Oro/Bundle/ProductBundle/Resources/config/oro/website_search.yml`:
    * removed configuration for next fields:
        * `name_LOCALIZATION_ID`
        * `sku`
        * `new_arrival`
        * `short_description_LOCALIZATION_ID`
        * `inventory_status`
    * all of this fields will be added to website search index as configuration for related product attributes
    * now in website search index some fields have new names:
        * `name_LOCALIZATION_ID` => `names_LOCALIZATION_ID`
        * `new_arrival` => `newArrival`
        * `short_description_LOCALIZATION_ID` => `shortDescriptions_LOCALIZATION_ID`

#### PromotionBundle
- Class `Oro\Bundle\PromotionBundle\Handler\CouponValidationHandler`
    - now extends from `Oro\Bundle\PromotionBundle\Handler\AbstractCouponHandler`
    - changes in constructor:
        - dependency on `Oro\Bundle\PromotionBundle\ValidationService\CouponApplicabilityValidationService` moved to `setCouponApplicabilityValidationService` setter
- Filtration services are now skippable. More details can be found in [documentation](https://github.com/orocommerce/orocommerce/tree/1.5.0/src/Oro/Bundle/PromotionBundle/README.md#filters-skippability-during-checkout).

#### RedirectBundle
 - Service `oro_redirect.url_cache` must be used instead `oro_redirect.url_storage_cache`
 - Interface `Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface` must be used as dependency instead of `Oro\Bundle\RedirectBundle\Cache\UrlStorageCache`
 - URL cache format for `storage` cache type was improved to decrease files size and speed up caches loading. 
 Old caches should be recalculated. Old caches format is still supported to simplify migration, to be able to use existing URL caches set `oro_redirect.url_storage_cache.split_deep` to 1. 
 To improve page rendering speed and decrease memory usage recommended to recalculate caches with `oro_redirect.url_storage_cache.split_deep` set to 2 (default value) or 3. Value depends on number of slugs in system 
 - By default if there are no pre-calculated URLs in cache them will be fetched from database on the fly and put to cache.

#### ShippingBundle
* Interface `Oro\Bundle\ShippingBundle\Context\Builder\ShippingContextBuilderInterface`:
   * Interface lost his `addLineItem` method. All line item collection should be processed with `setLineItems` and related interface `Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface`. 

#### WebsiteSearchBundle
* Entity `Oro\Bundle\WebsiteSearchBundle\Entity\IndexDecimal`:
    * changed decimal field `value`:
        * `precision` changed from `10` to `21`.
        * `scale` changed from `2` to `6`.
* Implementation can decorate original implementation of interface `Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface` that as service with tag `oro_entity_config.attribute_type`.
* Class `Oro\Bundle\SearchBundle\Engine\OrmIndexer`
    * The construction signature of was changed and the constructor was updated - `DbalStorer $dbalStorer` parameter removed.
* Class `Oro\Bundle\CatalogBundle\EventListener\DatagridListener`:
    * method `addCategoryRelation` flagged as deprecated.

## 1.4.0 (2017-09-29)
[Show detailed list of changes](incompatibilities-1-4.md)

### Added
#### PricingBundle
* Class `BaseProductPriceRepository`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/PricingBundle/Entity/Repository/BaseProductPriceRepository.php "Oro\Bundle\PricingBundle\Entity\Repository\BaseProductPriceRepository")</sup> got an abstract method:
    * `getPriceListIdsByProduct(Product $product)` - that should return array of Price Lists identifiers which contains price for given product
* Api for `ProductPrice`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/PricingBundle/Entity/ProductPrice.php "Oro\Bundle\PricingBundle\Entity\ProductPrice")</sup> entity was added. In sharding mode product prices can't be managed without `priceList` field, that's why in `get_list` action `priceList` filter is required and in all actions ID of entities has format `ProductPriceID-PriceListID`.
    * Class `PriceManagerDeleteHandler`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/PricingBundle/Api/ProductPrice/Delete/PriceManagerDeleteHandler.php "Oro\Bundle\PricingBundle\Api\ProductPrice\Delete\PriceManagerDeleteHandler")</sup> was added to correctly remove prices in sharding mode
    * Interface `PriceListIDContextStorageInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/PricingBundle/Api/ProductPrice/PriceListIDContextStorageInterface.php "Oro\Bundle\PricingBundle\Api\ProductPrice\PriceListIDContextStorageInterface")</sup> was added to abstract the way of storing price list id in an api context
    * Class `PriceListIDInContextStorage`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/PricingBundle/Api/ProductPrice/PriceListIDInContextStorage.php "Oro\Bundle\PricingBundle\Api\ProductPrice\PriceListIDInContextStorage")</sup> was added as a storage of price list id
    * Class `EnableShardingOnConfigProcessor`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/PricingBundle/Api/ProductPrice/Processor/EnableShardingOnConfigProcessor.php "Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\EnableShardingOnConfigProcessor")</sup> was added to set sharding query hints on config and 'price_list_id = :price_list_id' condition on query
    * Class `EnableShardingOnQueryProcessor`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/PricingBundle/Api/ProductPrice/Processor/EnableShardingOnQueryProcessor.php "Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\EnableShardingOnQueryProcessor")</sup> was added to set sharding query hints and 'price_list_id = :price_list_id' condition on query
    * Class `LoadNormalizedProductPriceWithNormalizedIdProcessor`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/PricingBundle/Api/ProductPrice/Processor/LoadNormalizedProductPriceWithNormalizedIdProcessor.php "Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\LoadNormalizedProductPriceWithNormalizedIdProcessor")</sup> was added to normalize an output of update/create requests
    * Class `NormalizeInputProductPriceIdProcessor`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/PricingBundle/Api/ProductPrice/Processor/NormalizeInputProductPriceIdProcessor.php "Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\NormalizeInputProductPriceIdProcessor")</sup> was added to transform id from request in 'guid-priceListId' format to 'guid' and save 'priceListId' to context
    * Class `NormalizeOutputProductPriceIdProcessor`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/PricingBundle/Api/ProductPrice/Processor/NormalizeOutputProductPriceIdProcessor.php "Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\NormalizeOutputProductPriceIdProcessor")</sup> was added to normalize entity ids that are returned in response
    * Class `SaveProductPriceProcessor`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/PricingBundle/Api/ProductPrice/Processor/SaveProductPriceProcessor.php "Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\SaveProductPriceProcessor")</sup> was added to correctly save price in sharding mode
    * Class `StorePriceListInContextByFilterProcessor`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/PricingBundle/Api/ProductPrice/Processor/StorePriceListInContextByFilterProcessor.php "Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\StorePriceListInContextByFilterProcessor")</sup> was added to save priceListId from filter to context
    * Class `StorePriceListInContextByProductPriceProcessor`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/PricingBundle/Api/ProductPrice/Processor/StorePriceListInContextByProductPriceProcessor.php "Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\StorePriceListInContextByProductPriceProcessor")</sup> was added to save priceListId from ProductPrice entity to context
    * Interface `ProductPriceIDByContextNormalizerInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/PricingBundle/Api/ProductPrice/ProductPriceIDByContextNormalizerInterface.php "Oro\Bundle\PricingBundle\Api\ProductPrice\ProductPriceIDByContextNormalizerInterface")</sup> was added to abstract the way of normalizing product price ids
    * Class `Oro\Component\ChainProcessor\ContextInterface\ProductPriceIDByPriceListIDNormalizer` was added to transform product price id to `ProductPriceID-PriceListID` format
    * Class `ResetPriceRuleFieldOnUpdateProcessor`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/PricingBundle/Api/ProductPrice/Processor/ResetPriceRuleFieldOnUpdateProcessor.php "Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\ResetPriceRuleFieldOnUpdateProcessor")</sup> was added to reset product price rule when one of the fields: `value`, `quantity`, `unit`, `currency` changes
#### ProductBundle
* Enabled API for ProductImage and ProductImageType and added documentation of usage in Product API.
#### RedirectBundle
* Added method to `SlugRepository`:
    * `getRawSlug` method to retrieve slug URL data 
* Added new interface:
    * `SluggableUrlProviderInterface`
* Added new URL providers:
    * `SluggableUrlCacheAwareProvider` takes slug URLs from persistent cache
    * `SluggableUrlDatabaseAwareProvider` takes slug URLs from the database  
### Changed
#### OrderBundle
* Form type `OrderDiscountItemType`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/OrderBundle/Form/Type/OrderDiscountItemType.php "Oro\Bundle\OrderBundle\Form\Type\OrderDiscountItemType")</sup> was changed for use in popup.
#### PaymentBundle
* Event `oro_payment.require_payment_redirect.PAYMENT_METHOD_IDENTIFIER` is no more specifically dispatched for each payment method. Use generic `oro_payment.require_payment_redirect` event instead.
#### PricingBundle
* Some inline underscore templates were moved to separate .html file for each template.
* Required option for layout block type 'product_prices' renamed from 'productUnitSelectionVisible' to 'isPriceUnitsVisible'
#### ProductBundle
* Product images and unit information for the grid are now part of the search index. In order to see image changes, for example, immediate reindexation is required. 
* Some inline underscore templates were moved to separate .html file for each template.
#### PromotionBundle
* Interface `DiscountInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/PromotionBundle/Discount/DiscountInterface.php "Oro\Bundle\PromotionBundle\Discount\DiscountInterface")</sup> now is fluent, please make sure that all classes which implement it return `$this` for `setPromotion` and  `setMatchingProducts` methods
    * `getPromotion()` method return value type changed from `Promotion`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/PromotionBundle/Entity/Promotion.php "Oro\Bundle\PromotionBundle\Entity\Promotion")</sup> to `PromotionDataInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/PromotionBundle/Entity/PromotionDataInterface.php "Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface")</sup>
    * `setPromotion()` method parameter's type changed from `Promotion`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/PromotionBundle/Entity/Promotion.php "Oro\Bundle\PromotionBundle\Entity\Promotion")</sup> to `PromotionDataInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/PromotionBundle/Entity/PromotionDataInterface.php "Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface")</sup>
#### RedirectBundle
* `MatchedUrlDecisionMaker` class should be used instead of `FrontendHelper` to check that current URL should be processed by Slugable Url matcher or generator
### Deprecated
#### ProductBundle
* Class `FrontendProductDatagridListener`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/ProductBundle/EventListener/FrontendProductDatagridListener.php "Oro\Bundle\ProductBundle\EventListener\FrontendProductDatagridListener")</sup>
    * dependency on `RegistryInterface` will soon be removed. `getProductRepository` and `getProductUnitRepository` flagged as deprecated.
### Removed
#### OrderBundle
* Form type `OrderDiscountItemsCollectionType`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/OrderBundle/Tests/Unit/Form/Type/OrderDiscountItemsCollectionType.php "Oro\Bundle\OrderBundle\Tests\Unit\Form\Type\OrderDiscountItemsCollectionType")</sup> and related `oroorder/js/app/views/discount-items-view` JS view were removed, new `OrderDiscountCollectionTableType`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/OrderBundle/Form/Type/OrderDiscountCollectionTableType.php "Oro\Bundle\OrderBundle\Form\Type\OrderDiscountCollectionTableType")</sup> and `oroorder/js/app/views/discount-collection-view` are introduced.
#### PromotionBundle
* Class `AppliedDiscountManager`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/PromotionBundle/Manager/AppliedDiscountManager.php "Oro\Bundle\PromotionBundle\Manager\AppliedDiscountManager")</sup>
    * class removed, logic was moved to `AppliedPromotionManager`
    * service of this manager removed, new `oro_promotion.applied_promotion_manager` service  was created
#### RedirectBundle
* Class `Router`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/RedirectBundle/Routing/Router.php "Oro\Bundle\RedirectBundle\Routing\Router")</sup>
    * removed method `setFrontendHelper`, `setMatchedUrlDecisionMaker` method added instead.

## 1.3.0 LTS (2017-07-28)
[Show detailed list of changes](incompatibilities-1-3.md)

### Added
#### CronBundle
* new collection form type for schedule intervals was added `ScheduleIntervalsCollectionType`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/CronBundle/Form/Type/ScheduleIntervalsCollectionType.php "Oro\Bundle\CronBundle\Form\Type\ScheduleIntervalsCollectionType")</sup>
* new form type for schedule interval was added `ScheduleIntervalType`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/CronBundle/Form/Type/ScheduleIntervalType.php "Oro\Bundle\CronBundle\Form\Type\ScheduleIntervalType")</sup>
#### PricingBundle
* added API for the following entities:
    - `PriceList`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Entity/PriceList.php "Oro\Bundle\PricingBundle\Entity\PriceList")</sup>
    - `PriceListSchedule`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Entity/PriceListSchedule.php "Oro\Bundle\PricingBundle\Entity\PriceListSchedule")</sup>
    - `PriceRule`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Entity/PriceRule.php "Oro\Bundle\PricingBundle\Entity\PriceRule")</sup>
    - `PriceListToCustomerGroup`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Entity/PriceListToCustomerGroup.php "Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup")</sup>
    - `PriceListCustomerGroupFallback`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Entity/PriceListCustomerGroupFallback.php "Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback")</sup>
    - `PriceListToCustomer`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Entity/PriceListToCustomer.php "Oro\Bundle\PricingBundle\Entity\PriceListToCustomer")</sup>
    - `PriceListCustomerFallback`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Entity/PriceListCustomerFallback.php "Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback")</sup>
* added API processors:
    - `HandlePriceListStatusChangeProcessor`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Api/Processor/HandlePriceListStatusChangeProcessor.php "Oro\Bundle\PricingBundle\Api\Processor\HandlePriceListStatusChangeProcessor")</sup> to handle price list status changes
    - `UpdatePriceListLexemesProcessor`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Api/Processor/UpdatePriceListLexemesProcessor.php "Oro\Bundle\PricingBundle\Api\Processor\UpdatePriceListLexemesProcessor")</sup> to update price rule lexemes while saving price list
    - `BuildCombinedPriceListOnScheduleDeleteListProcessor`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Api/Processor/BuildCombinedPriceListOnScheduleDeleteListProcessor.php "Oro\Bundle\PricingBundle\Api\Processor\BuildCombinedPriceListOnScheduleDeleteListProcessor")</sup> to rebuild combined price list while deleting list of price list schedules
    - `BuildCombinedPriceListOnScheduleDeleteProcessor`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Api/Processor/BuildCombinedPriceListOnScheduleDeleteProcessor.php "Oro\Bundle\PricingBundle\Api\Processor\BuildCombinedPriceListOnScheduleDeleteProcessor")</sup> to rebuild combined price list while deleting single price list schedule
    - `BuildCombinedPriceListOnScheduleSaveProcessor`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Api/Processor/BuildCombinedPriceListOnScheduleSaveProcessor.php "Oro\Bundle\PricingBundle\Api\Processor\BuildCombinedPriceListOnScheduleSaveProcessor")</sup> to rebuild combined price list while saving price list schedule
    - `UpdatePriceListContainsScheduleOnScheduleDeleteListProcessor`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Api/Processor/UpdatePriceListContainsScheduleOnScheduleDeleteListProcessor.php "Oro\Bundle\PricingBundle\Api\Processor\UpdatePriceListContainsScheduleOnScheduleDeleteListProcessor")</sup> to change price list contains schedule field while deleting list of price list schedules
    - `UpdatePriceListContainsScheduleOnScheduleDeleteProcessor`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Api/Processor/UpdatePriceListContainsScheduleOnScheduleDeleteProcessor.php "Oro\Bundle\PricingBundle\Api\Processor\UpdatePriceListContainsScheduleOnScheduleDeleteProcessor")</sup> to change price list contains schedule field while deleting single price list schedule
    - `UpdateLexemesOnPriceRuleDeleteListProcessor`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Api/UpdateLexemesOnPriceRuleDeleteListProcessor.php "Oro\Bundle\PricingBundle\Api\UpdateLexemesOnPriceRuleDeleteListProcessor")</sup> to update price rule lexemes while deleting list of price rules
    - `UpdateLexemesOnPriceRuleDeleteProcessor`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Api/UpdateLexemesOnPriceRuleDeleteProcessor.php "Oro\Bundle\PricingBundle\Api\UpdateLexemesOnPriceRuleDeleteProcessor")</sup> to update price rule lexemes while deleting single price rule
    - `UpdateLexemesPriceRuleProcessor`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Api/UpdateLexemesPriceRuleProcessor.php "Oro\Bundle\PricingBundle\Api\UpdateLexemesPriceRuleProcessor")</sup> to update price rule lexemes while saving price rule
    - `PriceListRelationTriggerHandlerForWebsiteAndCustomerProcessor`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Api/PriceListRelationTriggerHandlerForWebsiteAndCustomerProcessor.php "Oro\Bundle\PricingBundle\Api\PriceListRelationTriggerHandlerForWebsiteAndCustomerProcessor")</sup> to rebuild price lists when customer aware relational entities are modified
    - `PriceListRelationTriggerHandlerForWebsiteAndCustomerGroupProcessor`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Api/PriceListRelationTriggerHandlerForWebsiteAndCustomerGroupProcessor.php "Oro\Bundle\PricingBundle\Api\PriceListRelationTriggerHandlerForWebsiteAndCustomerGroupProcessor")</sup> to rebuild price lists when customer group aware relational entities are modified
* added `AddSchedulesToPriceListApiFormSubscriber`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Api/Form/AddSchedulesToPriceListApiFormSubscriber.php "Oro\Bundle\PricingBundle\Api\Form\AddSchedulesToPriceListApiFormSubscriber")</sup> for adding currently created schedule to price list
#### ProductBundle
* new class `VariantFieldProvider`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/ProductBundle/Provider/VariantFieldProvider.php "Oro\Bundle\ProductBundle\Provider\VariantFieldProvider")</sup> was added it introduces logic to fetch variant field for certain family calling `getVariantFields(AttributeFamily $attributeFamily)` method
* Brand functionality to ProductBundle was added
* adding skuUppercase to Product entity - the read-only property that consists uppercase version of sku, used to improve performance of searching by SKU 
#### SEOBundle
* metaTitles for `Product`, `Category`, `Page`, `WebCatalog`, `Brand` were added. MetaTitle is displayed as default view page title.
#### SaleBundle
* added Voter `FrontendQuotePermissionVoter`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/SaleBundle/Acl/Voter/FrontendQuotePermissionVoter.php "Oro\Bundle\SaleBundle\Acl\Voter\FrontendQuotePermissionVoter")</sup>, Checks if given Quote contains internal status, triggered only for Commerce Application.
* added Datagrid Listener `FrontendQuoteDatagridListener`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/SaleBundle/EventListener/Datagrid/FrontendQuoteDatagridListener.php "Oro\Bundle\SaleBundle\EventListener\Datagrid\FrontendQuoteDatagridListener")</sup>, appends frontend datagrid query with proper frontend internal statuses.
* added Subscriber `QuoteFormSubscriber`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/SaleBundle/Form/EventListener/QuoteFormSubscriber.php "Oro\Bundle\SaleBundle\Form\EventListener\QuoteFormSubscriber")</sup>, discards price modifications and free form inputs, if there are no permissions for those operations
* added new permission to `Quote` category
    - oro_quote_prices_override
    - oro_quote_review_and_approve
    - oro_quote_add_free_form_items
#### ValidationBundle
* added `BlankOneOf`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/ValidationBundle/Validator/Constraints/BlankOneOf.php "Oro\Bundle\ValidationBundle\Validator\Constraints\BlankOneOf")</sup> constraint and `BlankOneOfValidator`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/ValidationBundle/Validator/Constraints/BlankOneOfValidator.php "Oro\Bundle\ValidationBundle\Validator\Constraints\BlankOneOfValidator")</sup> validator for validating that one of some fields in a group should be blank
#### WebsiteBundle
* added `DefaultWebsiteSubscriber`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/WebsiteBundle/Form/EventSubscriber/DefaultWebsiteSubscriber.php "Oro\Bundle\WebsiteBundle\Form\EventSubscriber\DefaultWebsiteSubscriber")</sup> to set Default website when not provided on form.
### Changed
#### AuthorizeNetBundle
* AuthorizeNetBundle extracted to individual package. See [https://github.com/orocommerce/OroAuthorizeNetBundle](https://github.com/orocommerce/OroAuthorizeNetBundle) for details.
#### InventoryBundle
* inventory API has changed. Please, see [documentation](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/InventoryBundle/doc/api/inventory-level.md) for more information.
#### OrderBundle
* return value of method `Oro\Bundle\OrderBundle\Manager\AbstractAddressManager:getGroupedAddresses` changed from `array` to `TypedOrderAddressCollection`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/OrderBundle/Manager/TypedOrderAddressCollection.php "Oro\Bundle\OrderBundle\Manager\TypedOrderAddressCollection")</sup>
#### PayPalBundle
* class `PayflowIPCheckListener`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PayPalBundle/EventListener/Callback/PayflowIPCheckListener.php "Oro\Bundle\PayPalBundle\EventListener\Callback\PayflowIPCheckListener")</sup>
    - property `$allowedIPs` changed from `private` to `protected`
#### PaymentBundle
* subtotal and currency of payment context and its line items are optional now:
    - Interface `PaymentContextInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PaymentBundle/Context/PaymentContextInterface.php "Oro\Bundle\PaymentBundle\Context\PaymentContextInterface")</sup> was changed:
        - `getSubTotal` method can return either `Price` or `null`
        - `getCurrency` method can return either `string` or `null`
    - Interface `PaymentLineItemInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PaymentBundle/Context/PaymentLineItemInterface.php "Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface")</sup> was changed:
        - `getPrice` method can return either `Price` or `null`
#### PricingBundle
* service `oro_pricing.listener.product_unit_precision` was changed from `doctrine.event_listener` to `doctrine.orm.entity_listener`
    - setter methods `setProductPriceClass`, `setEventDispatcher`, `setShardManager` were removed. To set properties, constructor used instead.
#### ProductBundle
* class `BooleanVariantFieldValueHandler`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/ProductBundle/ProductVariant/VariantFieldValueHandler/BooleanVariantFieldValueHandler.php "Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler\BooleanVariantFieldValueHandler")</sup>
    - changed signature of `__construct` method. New dependency on `Symfony\Component\Translation\TranslatorInterface` was added.
* `ProductPriceFormatter` method `formatProductPrice` changed to expect `BaseProductPrice` attribute instead of `ProductPrice`.
#### SEOBundle
* service `oro_seo.event_listener.product_form_view`
    - dependency on `@request_stack` was removed
    - dependency on `@oro_entity.doctrine_helper` was removed
* service `oro_seo.event_listener.category_form_view`
    - dependency on `@request_stack` was removed
    - dependency on `@oro_entity.doctrine_helper` was removed
* service ` oro_seo.event_listener.page_form_view`
    - dependency on `@request_stack` was removed
    - dependency on `@oro_entity.doctrine_helper` was removed
* service `oro_seo.event_listener.content_node_form_view`
    - dependency on `@request_stack` was removed
    - dependency on `@oro_entity.doctrine_helper` was removed
#### SaleBundle
* updated entity `Quote`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/SaleBundle/Entity/Quote.php "Oro\Bundle\SaleBundle\Entity\Quote")</sup>
    - Added constant `FRONTEND_INTERNAL_STATUSES` that holds all available internal statuses for Commerce Application
    - Added new property `pricesChanged`, that indicates if prices were changed.
* following ACL permissions moved to `Quote` category
    - oro_quote_address_shipping_customer_use_any
    - oro_quote_address_shipping_customer_use_any_backend
    - oro_quote_address_shipping_customer_user_use_default
    - oro_quote_address_shipping_customer_user_use_default_backend
    - oro_quote_address_shipping_customer_user_use_any
    - oro_quote_address_shipping_customer_user_use_any_backend
    - oro_quote_address_shipping_allow_manual
    - oro_quote_address_shipping_allow_manual_backend
    - oro_quote_payment_term_customer_can_override
#### ShippingBundle
* redesign of Shipping Rule edit/create pages - changed Shipping Method Configurations block templates and functionality
    - `ShippingMethodConfigType`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/ShippingBundle/Form/Type/ShippingMethodConfigType.php "Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodConfigType")</sup> - added `methods_icons` variable
    - `oroshipping/js/app/views/shipping-rule-method-view` - changed options, functions, functionality
    - `ShippingMethodSelectType`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/ShippingBundle/Form/Type/ShippingMethodSelectType.php "Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodSelectType")</sup> - use `showIcon` option instead of `result_template_twig` and `selection_template_twig`
* subtotal and currency of shipping context and its line items are optional now:
    - Interface `ShippingContextInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/ShippingBundle/Context/ShippingContextInterface.php "Oro\Bundle\ShippingBundle\Context\ShippingContextInterface")</sup> was changed:
        - `getSubTotal` method can return either `Price` or `null`
        - `getCurrency` method can return either `string` or `null`
    - Interface `ShippingLineItemInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/ShippingBundle/Context/ShippingLineItemInterface.php "Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface")</sup> was changed:
        - `getPrice` method can return either `Price` or `null`
### Deprecated
#### CheckoutBundle
* layout `oro_payment_method_order_review` is deprecated since v1.3, will be removed in v1.6. Use 'oro_payment_method_order_submit' instead.
### Removed
#### CheckoutBundle
* class `CheckoutVoter`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/CheckoutBundle/Acl/Voter/CheckoutVoter.php "Oro\Bundle\CheckoutBundle\Acl\Voter\CheckoutVoter")</sup>
    - method `getSecurityFacade` was removed, `getAuthorizationChecker` method was added instead
#### FlatRateShippingBundle
* class `FlatRateMethodIdentifierGenerator`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/FlatRateShippingBundle/Method/Identifier/FlatRateMethodIdentifierGenerator.php "Oro\Bundle\FlatRateShippingBundle\Method\Identifier\FlatRateMethodIdentifierGenerator")</sup> is removed in favor of `PrefixedIntegrationIdentifierGenerator`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/IntegrationBundle/Generator/Prefixed/PrefixedIntegrationIdentifierGenerator.php "Oro\Bundle\IntegrationBundle\Generator\Prefixed\PrefixedIntegrationIdentifierGenerator")</sup>.
* previously deprecated `FlatRateMethodFromChannelBuilder`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/FlatRateShippingBundle/Builder/FlatRateMethodFromChannelBuilder.php "Oro\Bundle\FlatRateShippingBundle\Builder\FlatRateMethodFromChannelBuilder")</sup> is removed now. Use `FlatRateMethodFromChannelFactory`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/FlatRateShippingBundle/Factory/FlatRateMethodFromChannelFactory.php "Oro\Bundle\FlatRateShippingBundle\Factory\FlatRateMethodFromChannelFactory")</sup> instead.
#### OrderBundle
* removed protected method `AbstractOrderAddressType::getDefaultAddressKey`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/OrderBundle/Form/Type/AbstractOrderAddressType.php#L173 "Oro\Bundle\OrderBundle\Form\Type\AbstractOrderAddressType::getDefaultAddressKey")</sup>. Please, use method `TypedOrderAddressCollection::getDefaultAddressKey`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/OrderBundle/Manager/TypedOrderAddressCollection.php#L0 "Oro\Bundle\OrderBundle\Manager\TypedOrderAddressCollection::getDefaultAddressKey")</sup> instead
#### PayPalBundle
* class `Gateway`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PayPalBundle/PayPal/Payflow/Gateway.php "Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway")</sup>
    - constants `PRODUCTION_HOST_ADDRESS`, `PILOT_HOST_ADDRESS`, `PRODUCTION_FORM_ACTION`, `PILOT_FORM_ACTION` removed.
* previously deprecated `PayPalPasswordType`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PayPalBundle/Form/Type/PayPalPasswordType.php "Oro\Bundle\PayPalBundle\Form\Type\PayPalPasswordType")</sup> is removed. Use `OroEncodedPlaceholderPasswordType`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/FormBundle/Form/Type/OroEncodedPlaceholderPasswordType.php "Oro\Bundle\FormBundle\Form\Type\OroEncodedPlaceholderPasswordType")</sup> instead.
* previously deprecated interface `CardTypesDataProviderInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PayPalBundle/Settings/DataProvider/CardTypesDataProviderInterface.php "Oro\Bundle\PayPalBundle\Settings\DataProvider\CardTypesDataProviderInterface")</sup> is removed. Use `CreditCardTypesDataProviderInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PayPalBundle/Settings/DataProvider/CreditCardTypesDataProviderInterface.php "Oro\Bundle\PayPalBundle\Settings\DataProvider\CreditCardTypesDataProviderInterface")</sup> instead.
#### PaymentBundle
* previously deprecated class `PaymentMethodProvidersRegistry`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PaymentBundle/Method/Provider/Registry/PaymentMethodProvidersRegistry.php "Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistry")</sup> is removed, `CompositePaymentMethodProvider`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PaymentBundle/Method/Provider/CompositePaymentMethodProvider.php "Oro\Bundle\PaymentBundle\Method\Provider\CompositePaymentMethodProvider")</sup> should be used instead.
* previously deprecated method `PaymentStatusProvider::computeStatus`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/PaymentBundle/Provider/PaymentStatusProvider.php#L57 "Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider::computeStatus")</sup> is removed. Use `getPaymentStatus` instead.
* unused trait `CountryAwarePaymentConfigTrait`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PaymentBundle/Method/Config/CountryAwarePaymentConfigTrait.php "Oro\Bundle\PaymentBundle\Method\Config\CountryAwarePaymentConfigTrait")</sup> was removed.
#### PricingBundle
* form type `PriceListScheduleType`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Form/Type/PriceListScheduleType.php "Oro\Bundle\PricingBundle\Form\Type\PriceListScheduleType")</sup> was removed, use `ScheduleIntervalType`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/CronBundle/Form/Type/ScheduleIntervalType.php "Oro\Bundle\CronBundle\Form\Type\ScheduleIntervalType")</sup> instead
* constraint `SchedulesIntersection`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Validator/Constraints/SchedulesIntersection.php "Oro\Bundle\PricingBundle\Validator\Constraints\SchedulesIntersection")</sup> was removed, use `ScheduleIntervalsIntersection`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/CronBundle/Validator/Constraints/ScheduleIntervalsIntersection.php "Oro\Bundle\CronBundle\Validator\Constraints\ScheduleIntervalsIntersection")</sup> instead
* validator `SchedulesIntersectionValidator`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PricingBundle/Validator/Constraints/SchedulesIntersectionValidator.php "Oro\Bundle\PricingBundle\Validator\Constraints\SchedulesIntersectionValidator")</sup> was removed, use `ScheduleIntervalsIntersectionValidator`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/CronBundle/Validator/Constraints/ScheduleIntervalsIntersectionValidator.php "Oro\Bundle\CronBundle\Validator\Constraints\ScheduleIntervalsIntersectionValidator")</sup> instead
* js `oropricing/js/app/views/price-list-schedule-view` view was removed, use `orocron/js/app/views/schedule-intervals-view` instead
#### ProductBundle
* class `ProductStrategy`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/ProductBundle/ImportExport/Strategy/ProductStrategy.php "Oro\Bundle\ProductBundle\ImportExport\Strategy\ProductStrategy")</sup>
    - method `setSecurityFacade` was removed, `setTokenAccessor` method was added instead
#### SaleBundle
* removed protected method `QuoteAddressType::getDefaultAddressKey`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/SaleBundle/Form/Type/QuoteAddressType.php#L235 "Oro\Bundle\SaleBundle\Form\Type\QuoteAddressType::getDefaultAddressKey")</sup>. Please, use method `TypedOrderAddressCollection::getDefaultAddressKey`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/OrderBundle/Manager/TypedOrderAddressCollection.php#L0 "Oro\Bundle\OrderBundle\Manager\TypedOrderAddressCollection::getDefaultAddressKey")</sup> instead
#### ShippingBundle
* service `oro_shipping.shipping_method.registry` was removed, new `oro_shipping.shipping_method_provider` service is used instead
* class `ShippingMethodRegistry`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/ShippingBundle/Method/ShippingMethodRegistry.php "Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry")</sup> was removed, logic was moved to `CompositeShippingMethodProvider`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/ShippingBundle/Method/CompositeShippingMethodProvider.php "Oro\Bundle\ShippingBundle\Method\CompositeShippingMethodProvider")</sup>
    - method `getTrackingAwareShippingMethods` moved to class `TrackingAwareShippingMethodsProvider`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/ShippingBundle/Method/TrackingAwareShippingMethodsProvider.php "Oro\Bundle\ShippingBundle\Method\TrackingAwareShippingMethodsProvider")</sup>
* previously deprecated interface `IntegrationMethodIdentifierGeneratorInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/ShippingBundle/Identifier/IntegrationMethodIdentifierGeneratorInterface.php "Oro\Bundle\ShippingBundle\Identifier\IntegrationMethodIdentifierGeneratorInterface")</sup> is removed along with its implementations and usages. Use `IntegrationIdentifierGeneratorInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/IntegrationBundle/Generator/IntegrationIdentifierGeneratorInterface.php "Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface")</sup> instead.
* previously deprecated `ShippingMethodsConfigsRuleRepository::getConfigsWithEnabledRuleAndMethod`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/ShippingBundle/Entity/Repository/ShippingMethodsConfigsRuleRepository.php#L82 "Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository::getConfigsWithEnabledRuleAndMethod")</sup> method is removed now. Use `getEnabledRulesByMethod` method instead.
* previously deprecated `AbstractIntegrationRemovalListener`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/ShippingBundle/EventListener/AbstractIntegrationRemovalListener.php "Oro\Bundle\ShippingBundle\EventListener\AbstractIntegrationRemovalListener")</sup> is removed now. Use `IntegrationRemovalListener`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/ShippingBundle/EventListener/IntegrationRemovalListener.php "Oro\Bundle\ShippingBundle\EventListener\IntegrationRemovalListener")</sup> instead.
* `OroShippingBundle:Form:type/result.html.twig` and `OroShippingBundle:Form:type/selection.html.twig` - removed
#### UPSBundle
* class `UPSMethodIdentifierGenerator`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/UPSBundle/Method/Identifier/UPSMethodIdentifierGenerator.php "Oro\Bundle\UPSBundle\Method\Identifier\UPSMethodIdentifierGenerator")</sup> is removed in favor of `PrefixedIntegrationIdentifierGenerator`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/IntegrationBundle/Generator/Prefixed/PrefixedIntegrationIdentifierGenerator.php "Oro\Bundle\IntegrationBundle\Generator\Prefixed\PrefixedIntegrationIdentifierGenerator")</sup>.
#### WebsiteSearchBundle
* class `ReindexDemoDataListener`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/WebsiteSearchBundle/EventListener/ReindexDemoDataListener.php "Oro\Bundle\WebsiteSearchBundle\EventListener\ReindexDemoDataListener")</sup> was removed, `ReindexDemoDataFixturesListener`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/WebsiteSearchBundle/EventListener/ReindexDemoDataFixturesListener.php "Oro\Bundle\WebsiteSearchBundle\EventListener\ReindexDemoDataFixturesListener")</sup> class is used instead
## 1.2.0 (2017-06-01)
[Show detailed list of changes](incompatibilities-1-2.md)

### Added
#### CMSBundle
* content Blocks functionality was added. Please, see [documentation](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/CMSBundle/README.md) for more information.
#### OrderBundle
* `CHARGE_AUTHORIZED_PAYMENTS` permission was added for possibility to charge payment transaction
* capture button for payment authorize transactions was added in Payment History section, Capture button for order was removed
#### ShippingBundle
* if you have implemented a form that helps configure your custom shipping method (like the UPS integration form that is designed for the system UPS shipping method), you might need your custom shipping method validation. The `ShippingMethodValidatorInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/ShippingBundle/Method/Validator/ShippingMethodValidatorInterface.php "Oro\Bundle\ShippingBundle\Method\Validator\ShippingMethodValidatorInterface")</sup> and `oro_shipping.method_validator.basic` service were created to handle this. To add a custom logics, add a decorator for this service. Please refer to `oro_shipping.method_validator.decorator.basic_enabled_shipping_methods_by_rules` example.
* the `ShippingRuleViewMethodTemplateListener`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/ShippingBundle/EventListener/ShippingRuleViewMethodTemplateListener.php "Oro\Bundle\ShippingBundle\EventListener\ShippingRuleViewMethodTemplateListener")</sup> was created, and can be used for providing template of a shipping method on a shipping rule view page. 
### Changed
#### PricingBundle
* `productUnitSelectionVisible` option of the `ProductPricesType`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/PricingBundle/Layout/Block/Type/ProductPricesType.php "Oro\Bundle\PricingBundle\Layout\Block\Type\ProductPricesType")</sup> is required now.
### Deprecated
#### CatalogBundle
* the `CategoryRepository::getChildrenWithTitles`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/CatalogBundle/Entity/Repository/CategoryRepository.php#L87 "Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository::getChildrenWithTitles")</sup> was deprecated, use `CategoryRepository::getChildren`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/CatalogBundle/Entity/Repository/CategoryRepository.php#L64 "Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository::getChildren")</sup> instead.
#### FlatRateShippingBundle
* the `FlatRateMethodFromChannelBuilder`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/FlatRateShippingBundle/Builder/FlatRateMethodFromChannelBuilder.php#L64 "Oro\Bundle\FlatRateShippingBundle\Builder\FlatRateMethodFromChannelBuilder")</sup> was deprecated, use `FlatRateMethodFromChannelFactory`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/FlatRateShippingBundle/Factory/FlatRateMethodFromChannelFactory.php "Oro\Bundle\FlatRateShippingBundle\Factory\FlatRateMethodFromChannelFactory")</sup> instead.
#### PayPalBundle
* form type `PayPalPasswordType`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/PayPalBundle/Form/Type/PayPalPasswordType.php "Oro\Bundle\PayPalBundle\Form\Type\PayPalPasswordType")</sup> is deprecated, will be removed in v1.3. Please use `OroEncodedPlaceholderPasswordType`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/FormBundle/Form/Type/OroEncodedPlaceholderPasswordType.php "Oro\Bundle\FormBundle\Form\Type\OroEncodedPlaceholderPasswordType")</sup> instead.
* interface `CardTypesDataProviderInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/PayPalBundle/Settings/DataProvider/CardTypesDataProviderInterface.php "Oro\Bundle\PayPalBundle\Settings\DataProvider\CardTypesDataProviderInterface")</sup> is deprecated, will be removed in v1.3. Use `CreditCardTypesDataProviderInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/PayPalBundle/Settings/DataProvider/CreditCardTypesDataProviderInterface.php "Oro\Bundle\PayPalBundle\Settings\DataProvider\CreditCardTypesDataProviderInterface")</sup> instead.
#### PaymentBundle
* for supporting same approaches for working with payment methods, `PaymentMethodProvidersRegistryInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/PaymentBundle/Method/Provider/Registry/PaymentMethodProvidersRegistryInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface")</sup> and its implementation were deprecated. Related deprecation is `PaymentMethodProvidersPass`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/PaymentBundle/DependencyInjection/Compiler/PaymentMethodProvidersPass.php "Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\PaymentMethodProvidersPass")</sup>. `CompositePaymentMethodProvider`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/PaymentBundle/Method/Provider/CompositePaymentMethodProvider.php "Oro\Bundle\PaymentBundle\Method\Provider\CompositePaymentMethodProvider")</sup> which implements `PaymentMethodProviderInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/PaymentBundle/Method/Provider/PaymentMethodProviderInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface")</sup> was added instead.
#### ShippingBundle
* `ShippingMethodsConfigsRuleRepository::getConfigsWithEnabledRuleAndMethod`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/ShippingBundle/Entity/Repository/ShippingMethodsConfigsRuleRepository.php#L82 "Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository::getConfigsWithEnabledRuleAndMethod")</sup> method deprecated because it completely duplicate `getEnabledRulesByMethod`
* the `IntegrationMethodIdentifierGeneratorInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/ShippingBundle/Method/Identifier/IntegrationMethodIdentifierGeneratorInterface.php "Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface")</sup> was deprecated, the `IntegrationIdentifierGeneratorInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/IntegrationBundle/Generator/IntegrationIdentifierGeneratorInterface.php "Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface")</sup> should be used instead.
### Removed
#### MoneyOrderBundle
* the class `MoneyOrder`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/MoneyOrderBundle/Method/MoneyOrder.php "Oro\Bundle\MoneyOrderBundle\Method\MoneyOrder")</sup> constant `TYPE` was removed.
#### OrderBundle
* `oro_order_capture` operation was removed, `oro_order_payment_transaction_capture` should be used instead
#### PayPalBundle
* JS credit card validators were moved to `PaymentBundle`. List of moved components:
    - `oropaypal/js/lib/jquery-credit-card-validator`
    - `oropaypal/js/validator/credit-card-expiration-date`
    - `oropaypal/js/validator/credit-card-expiration-date-not-blank`
    - `oropaypal/js/validator/credit-card-number`
    - `oropaypal/js/validator/credit-card-type`
    - `oropaypal/js/adapter/credit-card-validator-adapter`
#### PaymentBundle
* the `CaptureAction`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Action/CaptureAction.php#L7 "Oro\Bundle\PaymentBundle\Action\CaptureAction")</sup> class was removed. Use `PaymentTransactionCaptureAction`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/PaymentBundle/Action/PaymentTransactionCaptureAction.php "Oro\Bundle\PaymentBundle\Action\PaymentTransactionCaptureAction")</sup> instead.
#### PricingBundle
* the `AjaxProductPriceController::getProductPricesByCustomer`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PricingBundle/Controller/AjaxProductPriceController.php#L26 "Oro\Bundle\PricingBundle\Controller\AjaxProductPriceController")</sup> method was removed, logic was moved to `getProductPricesByCustomerAction`
* the `AjaxPriceListController::getPriceListCurrencyList`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PricingBundle/Controller/AjaxPriceListController.php#L63 "Oro\Bundle\PricingBundle\Controller\AjaxPriceListController::getPriceListCurrencyList")</sup> method was removed, logic was moved to `getPriceListCurrencyListAction` method
#### UPSBundle
* the following methods in class `AjaxUPSController`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/UPSBundle/Controller/AjaxUPSController.php "Oro\Bundle\UPSBundle\Controller\AjaxUPSController")</sup> were renamed:
   - `getShippingServicesByCountry`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Controller/AjaxUPSController.php#L29 "Oro\Bundle\UPSBundle\Controller\AjaxUPSController::getShippingServicesByCountry")</sup> is removed, logic is moved to `getShippingServicesByCountryAction` method
   - `validateConnection`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Controller/AjaxUPSController.php#L54 "Oro\Bundle\UPSBundle\Controller\AjaxUPSController::validateConnection")</sup> is removed, logic is moved to `validateConnectionAction` method
* the following properties in class `UPSTransport`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/UPSBundle/Entity/UPSTransport.php "Oro\Bundle\UPSBundle\Entity\UPSTransport")</sup> were renamed:
   - `$testMode`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Entity/UPSTransport.php#L35 "Oro\Bundle\UPSBundle\Entity\UPSTransport::$testMode")</sup> is removed, use `$upsTestMode` instead
   - `$apiUser`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Entity/UPSTransport.php#L42 "Oro\Bundle\UPSBundle\Entity\UPSTransport::$apiUser")</sup> is removed, use `$upsApiUser` instead
   - `$apiPassword`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Entity/UPSTransport.php#L49 "Oro\Bundle\UPSBundle\Entity\UPSTransport::$apiPassword")</sup> is removed, use  `$upsApiPassword` instead
   - `$apiKey`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Entity/UPSTransport.php#L56 "Oro\Bundle\UPSBundle\Entity\UPSTransport::$apiKey")</sup> is removed, use `$upsApiKey` instead
   - `$shippingAccountNumber`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Entity/UPSTransport.php#L63 "Oro\Bundle\UPSBundle\Entity\UPSTransport::$shippingAccountNumber")</sup> is removed, use `$upsShippingAccountNumber` instead
   - `$shippingAccountName`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Entity/UPSTransport.php#L70 "Oro\Bundle\UPSBundle\Entity\UPSTransport::$shippingAccountName")</sup> is removed, use `$upsShippingAccountName` instead
   - `$pickupType`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Entity/UPSTransport.php#L77 "Oro\Bundle\UPSBundle\Entity\UPSTransport::$pickupType")</sup> is removed, use `$upsPickupType` instead
   - `$unitOfWeight`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Entity/UPSTransport.php#L84 "Oro\Bundle\UPSBundle\Entity\UPSTransport::$unitOfWeight")</sup> is removed, use `$upsUnitOfWeight` instead
   - `$country`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Entity/UPSTransport.php#L92 "Oro\Bundle\UPSBundle\Entity\UPSTransport::$country")</sup> is removed, us `$upsCountry` instead
   - `$invalidateCacheAt`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Entity/UPSTransport.php#L138 "Oro\Bundle\UPSBundle\Entity\UPSTransport::$invalidateCacheAt")</sup> is removed, use `$upsInvalidateCacheAt` instead
## 1.1.0 (2017-03-31)
[Show detailed list of changes](incompatibilities-1-1.md)

### Added
#### CatalogBundle
* the [`CategoryBreadcrumbProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Layout/DataProvider/CategoryBreadcrumbProvider.php "Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryBreadcrumbProvider") was added as a data provider for breadcrumbs.
#### CustomerBundle
* `commerce` configurable permission was added for View and Edit pages of the Customer Role in backend area (aka management console) (see [configurable-permissions.md](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/SecurityBundle/Resources/doc/configurable-permissions.md) for details.
* `commerce_frontend` configurable permission was added for View and Edit pages of the Customer Role in frontend area (aka front store)(see [configurable-permissions.md](https://github.com/oroinc/platform/tree/2.1.0/src/Oro/Bundle/SecurityBundle/Resources/doc/configurable-permissions.md) for details.
#### MoneyOrderBundle
* added implementation of payment through integration.
* based on the changes in `PaymentBundle`, the following classes were added:
  * [`MoneyOrderMethodProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Method/Provider/MoneyOrderMethodProvider.php "Oro\Bundle\MoneyOrderBundle\Method\Provider\MoneyOrderMethodProvider") that provides Money Order payment methods.
  * [`MoneyOrderMethodViewProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Method/View/Provider/MoneyOrderMethodViewProvider.php "Oro\Bundle\MoneyOrderBundle\Method\View\Provider\MoneyOrderMethodViewProvider") that provides Money Order payment method views.
#### OrderBundle
* payment history section with payment transactions for current order was added to the order view page. The `VIEW_PAYMENT_HISTORY` permission was added for viewing payment history section.
#### PayPalBundle
* implementation was changed using IntegrationBundle (refer to PaymentBundle and IntegrationBundle for details):
    - Class `PayPalSettings`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Entity/PayPalSettings.php "Oro\Bundle\PayPalBundle\Entity\PayPalSettings")</sup> was created instead of `Configuration`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/DependencyInjection/Configuration.php "Oro\Bundle\PayPalBundle\DependencyInjection\Configuration")</sup>
    - Class `PayPalExpressCheckoutPaymentMethod`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/PayPalExpressCheckoutPaymentMethod.php "Oro\Bundle\PayPalBundle\Method\PayPalExpressCheckoutPaymentMethod")</sup> was added instead of removed classes `PayflowExpressCheckout`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/PayflowExpressCheckout.php "Oro\Bundle\PayPalBundle\Method\PayflowExpressCheckout")</sup>, `PayPalPaymentsProExpressCheckout`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/PayPalPaymentsProExpressCheckout.php "Oro\Bundle\PayPalBundle\Method\PayPalPaymentsProExpressCheckout")</sup>
    - Class `PayPalCreditCardPaymentMethod`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/PayPalCreditCardPaymentMethod.php "Oro\Bundle\PayPalBundle\Method\PayPalCreditCardPaymentMethod")</sup> was added instead of removed classes `PayflowGateway`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/PayflowGateway.php "Oro\Bundle\PayPalBundle\Method\PayflowGateway")</sup>, `PayPalPaymentsPro`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/PayPalPaymentsPro.php "Oro\Bundle\PayPalBundle\Method\PayPalPaymentsPro")</sup> 
    - Class `PayPalExpressCheckoutPaymentMethodView`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/View/PayPalExpressCheckoutPaymentMethodView.php "Oro\Bundle\PayPalBundle\Method\View\PayPalExpressCheckoutPaymentMethodView")</sup> was added instead of removed classes `PayflowExpressCheckout`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/View/PayflowExpressCheckout.php "Oro\Bundle\PayPalBundle\Method\View\PayflowExpressCheckout")</sup>, `PayPalPaymentsProExpressCheckout`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/View/PayPalPaymentsProExpressCheckout.php "Oro\Bundle\PayPalBundle\Method\View\PayPalPaymentsProExpressCheckout")</sup>
    - Class `PayPalCreditCardPaymentMethodView`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/View/PayPalCreditCardPaymentMethodView.php "Oro\Bundle\PayPalBundle\Method\View\PayPalCreditCardPaymentMethodView")</sup> was added instead of removed classes `PayflowGateway`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/View/PayflowGateway.php "Oro\Bundle\PayPalBundle\Method\View\PayflowGateway")</sup>, `PayPalPaymentsPro`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/View/PayPalPaymentsPro.php "Oro\Bundle\PayPalBundle\Method\View\PayPalPaymentsPro")</sup>
* according to changes in PaymentBundle were added:
    - `CreditCardMethodProvider`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/Provider/CreditCardMethodProvider.php "Oro\Bundle\PayPalBundle\Method\Provider\CreditCardMethodProvider")</sup> for providing *PayPal Credit Card Payment Methods*
    - `CreditCardMethodViewProvider`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/View/Provider/CreditCardMethodViewProvider.php "Oro\Bundle\PayPalBundle\Method\View\Provider\CreditCardMethodViewProvider")</sup> for providing *PayPal Credit Card Payment Method Views*
    - `ExpressCheckoutMethodProvider`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/Provider/ExpressCheckoutMethodProvider.php "Oro\Bundle\PayPalBundle\Method\Provider\ExpressCheckoutMethodProvider")</sup> for providing *PayPal Express Checkout Payment Methods*
    - `ExpressCheckoutMethodViewProvider`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/View/Provider/ExpressCheckoutMethodViewProvider.php "Oro\Bundle\PayPalBundle\Method\View\Provider\ExpressCheckoutMethodViewProvider")</sup> for providing *PayPal Express Checkout Payment Method Views*
* added implementation of payment through integration.
#### PaymentBundle
* the *organization* ownership type was added for the [`PaymentMethodsConfigsRule`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Entity/PaymentMethodsConfigsRule.php "Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule") entity.
* in order to have possibility to create more than one payment method of the same type, the PaymentBundle was significantly changed **with backward compatibility break**:
  - A new [`PaymentMethodProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/PaymentMethodProviderInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface") interface was added. This interface should be implemented in any payment method provider class that is responsible for providing of any payment method.
  - A new [`PaymentMethodViewProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewProviderInterface.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface") interface was added. This interface should be implemented in any payment method view provider class that is responsible for providing of any payment method view.
  - Any payment method provider should be registered in the service definitions with tag *oro_payment.payment_method_provider*.
  - Any payment method view provider should be registered in the service definitions with tag *oro_payment.payment_method_view_provider*.
  - Each payment method provider should provide one or more payment methods which should implement [`PaymentMethodInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/PaymentMethodInterface.php "Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface").
  - Each payment method view provider should provide one or more payment method views which should implement [`PaymentMethodViewInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewInterface.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface").
  - To aggregate the shared logic of all payment method providers, the [`AbstractPaymentMethodProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/AbstractPaymentMethodProvider.php "Oro\Bundle\PaymentBundle\Method\Provider\AbstractPaymentMethodProvider") was created. Any new payment method provider should extend this class.
  - To aggregate the shared logic of all payment method view providers, the [`AbstractPaymentMethodViewProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/AbstractPaymentMethodViewProvider.php "Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider") was created. Any new payment method view provider should extend this class.
#### PaymentTermBundle
* added implementation of payment through integration.
* class `PaymentTermView`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentTermBundle/Method/View/PaymentTermView.php "Oro\Bundle\PaymentTermBundle\Method\View\PaymentTermView")</sup> now has two additional methods due to implementing `PaymentMethodViewInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewInterface.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface")</sup>
    - getAdminLabel() is used to display labels in admin panel
    - getPaymentMethodIdentifier() used to properly display different methods in frontend
#### ProductBundle
* added classes that can decorate `Product`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/Entity/Product.php "Oro\Bundle\ProductBundle\Entity\Product")</sup> to have virtual fields:
    - `VirtualFieldsProductDecoratorFactory`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/VirtualFields/VirtualFieldsProductDecoratorFactory.php "Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory")</sup> is the class that should be used to create a decorated `Product`
    - `VirtualFieldsProductDecorator`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/VirtualFields/VirtualFieldsProductDecorator.php "Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecorator")</sup> is the class that decorates `Product`
    - `VirtualFieldsSelectQueryConverter`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/VirtualFields/QueryDesigner/VirtualFieldsSelectQueryConverter.php "Oro\Bundle\ProductBundle\VirtualFields\QueryDesigner\VirtualFieldsSelectQueryConverter")</sup> this converter is used inside of `VirtualFieldsProductDecorator`
    - `VirtualFieldsProductQueryDesigner`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/VirtualFields/QueryDesigner/VirtualFieldsProductQueryDesigner.php "Oro\Bundle\ProductBundle\VirtualFields\QueryDesigner\VirtualFieldsProductQueryDesigner")</sup> this query designer is used inside of `VirtualFieldsProductDecorator`
#### RuleBundle
* added `RuleInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Entity/RuleInterface.php "Oro\Bundle\RuleBundle\Entity\RuleInterface")</sup> this interface should now be used for injection instead of `Rule` in bundles that implement `RuleBundle` functionality
* added classes for handling enable/disable `Rule` actions - use them to define corresponding services
    - `StatusMassActionHandler`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/Extension/MassAction/StatusMassActionHandler.php "Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\StatusMassActionHandler")</sup>
    - `StatusEnableMassAction`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/Extension/MassAction/Actions/StatusEnableMassAction.php "Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction")</sup>
    - `RuleActionsVisibilityProvider`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/RuleActionsVisibilityProvider.php "Oro\Bundle\RuleBundle\Datagrid\RuleActionsVisibilityProvider")</sup>
* added `RuleActionsVisibilityProvider` that should be used to define action visibility configuration in datagrids with `Rule` entity fields
#### ShippingBundle
* [`IntegrationRemovalListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Method/EventListener/IntegrationRemovalListener.php "Oro\Bundle\ShippingBundle\Method\EventListener\IntegrationRemovalListener") class was created to be used instead of [`AbstractIntegrationRemovalListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Method/EventListener/AbstractIntegrationRemovalListener.php "Oro\Bundle\ShippingBundle\Method\EventListener\AbstractIntegrationRemovalListener")
#### UPSBundle
* *Check UPS Connection* button was added on UPS integration page. Please, see [documentation](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Resources/doc/credentials-validation.md) for more information.
#### WebCatalog Component
* new [`WebCatalogAwareInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Component/WebCatalog/Entity/WebCatalogAwareInterface.php "Oro\Component\WebCatalog\Entity\WebCatalogAwareInterface") became available for entities which are aware of `WebCatalogs`.
* new [`WebCatalogUsageProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Component/WebCatalog/Provider/WebCatalogUsageProviderInterface.php "Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface") interface:
    - provides information about assigned `WebCatalogs` to given entities (passed as an argument)
    - provides information about usage of `WebCatalog` by id
#### WebCatalogBundle
* the [`WebCatalogBreadcrumbDataProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Layout/DataProvider/WebCatalogBreadcrumbDataProvider.php "Oro\Bundle\WebCatalogBundle\Layout\DataProvider\WebCatalogBreadcrumbDataProvider") class was created. 
    - `getItems` method returns breadcrumbs array
### Changed
#### CatalogBundle
* the [`CategoryExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Twig/CategoryExtension.php "Oro\Bundle\CatalogBundle\Twig\CategoryExtension") class changed:
    - The construction signature of was changed and the constructor was updated with the new `ContainerInterface $container` parameter.
#### CommerceMenuBundle
* the bundle <sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CommerceMenuBundle "Oro\Bundle\CommerceMenuBundle") was moved from the [`OroCommerce`](https://github.com/orocommerce/orocommerce) package into the [`OroCRM Customer Portal`](#"https://github.com/orocrm/customer-portal") package.
* the [`MenuExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CommerceMenuBundle/Twig/MenuExtension.php "Oro\Bundle\CommerceMenuBundle\Twig\MenuExtension") class was updated with the following change:
    - The construction signature of was changed and the constructor accepts only one `ContainerInterface $container` parameter.
#### CustomerBundle
* the bundle <sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CustomerBundle "Oro\Bundle\CustomerBundle") was moved from the [`OroCommerce`](https://github.com/orocommerce/orocommerce) package into the [`OroCRM Customer Portal`](https://github.com/orocrm/customer-portal) package.
* the [`FrontendOwnerTreeProvider::_construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CustomerBundle/OwnerFrontendOwnerTreeProvider.php "Oro\Bundle\CustomerBundle\Owner\FrontendOwnerTreeProvider") method was added with the following signature:
  ```
  __construct(
        ManagerRegistry $doctrine,
        DatabaseChecker $databaseChecker,
        CacheProvider $cache,
        MetadataProviderInterface $ownershipMetadataProvider,
        TokenStorageInterface $tokenStorage
    )
  ```
* the construction signature of the [`CustomerExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CustomerBundle/Twig/CustomerExtension.php "Oro\Bundle\CustomerBundle\Twig\CustomerExtension") class was changed and the constructor accepts only one `ContainerInterface $container` parameter.
#### FlatRateBundle
* the bundle <sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/FlatRateBundle/ "Oro\Bundle\FlatRateBundle") was renamed to [`FlatRateShippingBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/FlatRateShippingBundle/ "Oro\Bundle\FlatRateShippingBundle") 
#### FrontendBundle
* the bundle <sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/FrontendBundle "Oro\Bundle\FrontendBundle") moved from the [`OroCommerce`](https://github.com/orocommerce/orocommerce) package into the [`OroCRM Customer Portal`](https://github.com/orocrm/customer-portal) package.
#### FrontendLocalizationBundle
* the service definition for `oro_frontend_localization.extension.transtation_packages_provider` was updated in a following way: 
    - the class changed to [`UPSTransport`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/FrontendBundle/Provider/TranslationPackagesProviderExtension.php "Oro\Bundle\FrontendBundle\Provider\TranslationPackagesProviderExtension")
    - the publicity set to `false`
#### MoneyOrderBundle
* the bundle <sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle "Oro\Bundle\MoneyOrderBundle") implementation was changed using `IntegrationBundle` (refer to `PaymentBundle` and `IntegrationBundle` for details).
#### PayPalBundle
* implementation was changed using IntegrationBundle (refer to PaymentBundle and IntegrationBundle for details).
#### PaymentTermBundle
* implementation was changed using IntegrationBundle (refer to PaymentBundle and IntegrationBundle for details).
* PaymentTerm implementation was changed using IntegrationBundle (refer to PaymentBundle and IntegrationBundle for details).
#### PricingBundle
* class `CombinedPriceListRepository`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PricingBundle/Entity/Repository/CombinedPriceListRepository.php "Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository")</sup> changes:
    - changed the return type of `getCombinedPriceListsByPriceList` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    - changed the return type of `getCombinedPriceListsByPriceLists` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    - changed the return type of `getCPLsForPriceCollectByTimeOffset` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
* class `PriceListCustomerFallbackRepository`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PricingBundle/Entity/Repository/PriceListCustomerFallbackRepository.php "Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerFallbackRepository")</sup> changes:
    - changed the return type of `getCustomerIdentityByGroup` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
* class `PriceListCustomerGroupFallbackRepository`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PricingBundle/Entity/Repository/PriceListCustomerGroupFallbackRepository.php "Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerGroupFallbackRepository")</sup> changes:
    - changed the return type of `getCustomerIdentityByWebsite` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
* class `PriceListRepository`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PricingBundle/Entity/Repository/PriceListRepository.php "Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository")</sup> changes:
    - changed the return type of `getPriceListsWithRules` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
* class `PriceListToCustomerGroupRepository`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PricingBundle/Entity/Repository/PriceListToCustomerGroupRepository.php "Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository")</sup> changes:
    - changed the return type of `getCustomerGroupIteratorByDefaultFallback` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    - changed the return type of `getIteratorByPriceList` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
* class `PriceListToCustomerRepository`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PricingBundle/Entity/Repository/PriceListToCustomerRepository.php "Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository")</sup> changes:
    - changed the return type of `getCustomerIteratorByDefaultFallback` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    - changed the return type of `getCustomerWebsitePairsByCustomerGroupIterator` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    - changed the return type of `getIteratorByPriceList` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
* class `PriceListToWebsiteRepository`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PricingBundle/Entity/Repository/PriceListToWebsiteRepository.php "Oro\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository")</sup> changes:
    - changed the return type of `getWebsiteIteratorByDefaultFallback` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
#### TaxBundle
* the following methods were updated: 
  - [`AbstractTaxCode::setCreatedAt`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Entity/AbstractTaxCode.php "Oro\Bundle\TaxBundle\Entity\AbstractTaxCode") (pass `\DateTime` as a first argument of the method instead of `mixed`)
  - [`AbstractTaxCode::setUpdatedAt`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Entity/AbstractTaxCode.php "Oro\Bundle\TaxBundle\Entity\AbstractTaxCode") (pass `\DateTime` as a first argument of the method instead of `mixed`)
  - [`Tax::setCreatedAt`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Entity/Tax.php "Oro\Bundle\TaxBundle\Entity\Tax") (pass `\DateTime` as a first argument of the method instead of `mixed`)
  - [`Tax::setUpdatedAt`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Entity/Tax.php "Oro\Bundle\TaxBundle\Entity\Tax") (pass `\DateTime` as a first argument of the method instead of `mixed`)
  - [`TaxJurisdiction::setCreatedAt`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Entity/TaxJurisdiction.php "Oro\Bundle\TaxBundle\Entity\TaxJurisdiction") (pass `\DateTime` as a first argument of the method instead of `mixed`)
  - [`TaxJurisdiction::setUpdatedAt`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Entity/TaxJurisdiction.php "Oro\Bundle\TaxBundle\Entity\TaxJurisdiction") (pass `\DateTime` as a first argument of the method instead of `mixed`)
  - [`TaxRule::setCreatedAt`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Entity/TaxRule.php "Oro\Bundle\TaxBundle\Entity\TaxRule") (pass `\DateTime` as a first argument of the method instead of `mixed`)
  - [`TaxRule::setUpdatedAt`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Entity/TaxRule.php "Oro\Bundle\TaxBundle\Entity\TaxRule") (pass `\DateTime` as a first argument of the method instead of `mixed`)
  - [`ZipCode::setCreatedAt`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Entity/ZipCode.php "Oro\Bundle\TaxBundle\Entity\ZipCode") (pass `\DateTime` as a first argument of the method instead of `mixed`)
  - [`ZipCode::setUpdatedAt`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/TaxBundle/Entity/ZipCode.php "Oro\Bundle\TaxBundle\Entity\ZipCode") (pass `\DateTime` as a first argument of the method instead of `mixed`)
#### VisibilityBundle
* in [`AbstractCustomerPartialUpdateDriver`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/VisibilityBundle/Driver/AbstractCustomerPartialUpdateDriver.php "Oro\Bundle\VisibilityBundle\Driver\AbstractCustomerPartialUpdateDriver"), the return type of the `getCustomerVisibilityIterator` method changed from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`.
#### WebsiteBundle
* the bundle <sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/WebsiteBundle "Oro\Bundle\WebsiteBundle") moved from the [`OroCommerce`](https://github.com/orocommerce/orocommerce) package into the [`OroCRM Customer Portal`](https://github.com/orocrm/customer-portal) package.
* the [`WebsiteBundle`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/WebsiteBundle "Oro\Bundle\WebsiteBundle") moved from the [`OroCommerce`](https://github.com/orocommerce/orocommerce) package into the [`OroCRM Customer Portal`](https://github.com/orocrm/customer-portal) package.
* the [`OroWebsiteExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebsiteBundle/Twig/OroWebsiteExtension.php "Oro\Bundle\WebsiteBundle\Twig\OroWebsiteExtension") class changed:
    - The construction signature of was changed and the constructor was updated to have only one parameter: `ContainerInterface $container`.
* the [`WebsitePathExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebsiteBundle/Twig/WebsitePathExtension.php "Oro\Bundle\WebsiteBundle\Twig\WebsitePathExtension") class changed:
        - The construction signature of was changed and the constructor was updated to have only one parameter: `ContainerInterface $container`.
#### WebsiteSearchBundle
* the `Driver::writeItem` and `Driver::flushWrites` should be used instead of `Driver::saveItems`
### Deprecated
#### CatalogBundle
* the [`CategoryProvider::getBreadcrumbs`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Layout/DataProvider/CategoryProvider.php "Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider") method  is deprecated. Please use
    [`CategoryBreadcrumbProvider::getItems()`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Layout/DataProvider/CategoryBreadcrumbProvider.php "Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryBreadcrumbProvider") instead.
#### InventoryBundle
* in the`/api/inventorylevels` REST API resource, the `productUnitPrecision.unit.code` filter was marked as deprecated. The `productUnitPrecision.unit.id` filter should be used instead.
#### ShippingBundle
* [`AbstractIntegrationRemovalListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Method/EventListener/AbstractIntegrationRemovalListener.php "Oro\Bundle\ShippingBundle\Method\EventListener\AbstractIntegrationRemovalListener") was deprecated, [`IntegrationRemovalListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Method/EventListener/IntegrationRemovalListener.php "Oro\Bundle\ShippingBundle\Method\EventListener\IntegrationRemovalListener") was created instead.
### Removed
#### CatalogBundle
* the [`CategoryExtension::setContainer`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CatalogBundle/Twig/CategoryExtension.php "Oro\Bundle\CatalogBundle\Twig\CategoryExtension") method was removed.
* the [`CategoryExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Twig/CategoryExtension.php "Oro\Bundle\CatalogBundle\Twig\CategoryExtension") class changed:
    - The `setContainer` method was removed.
* the [`CategoryPageVariantType`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Form/Type/CategoryPageVariantType.php "Oro\Bundle\CatalogBundle\Form\Type\CategoryPageVariantType") was removed and the logic moved to [`PageVariantTypeExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Form/Extension/PageVariantTypeExtension.php "Oro\Bundle\WebCatalogBundle\Form\Extension\PageVariantTypeExtension")
#### CustomerBundle
* the property `protected $securityProvider` was removed from the [`CustomerExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CustomerBundle/Twig/CustomerExtension.php "Oro\Bundle\CustomerBundle\Twig\CustomerExtension") class.
* the [`FrontendCustomerUserRoleOptionsProvider`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CustomerBundle/Layout/DataProvider/FrontendCustomerUserRoleOptionsProvider.php "Oro\Bundle\CustomerBundle\Layout\DataProvider\FrontendCustomerUserRoleOptionsProvider") class was removed and replaced with:
    - [`FrontendCustomerUserRoleCapabilitySetOptionsProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CustomerBundle/Layout/DataProvider/FrontendCustomerUserRoleCapabilitySetOptionsProvider.php "Oro\Bundle\CustomerBundle\Layout\DataProvider\FrontendCustomerUserRoleCapabilitySetOptionsProvider") for getting capability set options
    - [`FrontendCustomerUserRoleTabOptionsProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CustomerBundle/Layout/DataProvider/FrontendCustomerUserRoleTabOptionsProvider.php "Oro\Bundle\CustomerBundle\Layout\DataProvider\FrontendCustomerUserRoleTabOptionsProvider") for getting tab options
#### MoneyOrderBundle
* the [`Configuration`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/MoneyOrderBundle/DependencyInjection/Configuration.php "Oro\Bundle\MoneyOrderBundle\DependencyInjection\Configuration") class was removed. Use [`MoneyOrderSettings`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Entity/MoneyOrderSettings.php "Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings") entity that extends the [`Transport`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/IntegrationBundle/Entity/Transport.php "Oro\Bundle\IntegrationBundle\Entity\Transport") class to store payment integration properties.
#### PayPalBundle
* implementation was changed using IntegrationBundle (refer to PaymentBundle and IntegrationBundle for details):
    - Class `Configuration`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/DependencyInjection/Configuration.php "Oro\Bundle\PayPalBundle\DependencyInjection\Configuration")</sup> was removed and instead `PayPalSettings`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Entity/PayPalSettings.php "Oro\Bundle\PayPalBundle\Entity\PayPalSettings")</sup> was created - entity that implements `Transport`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/IntegrationBundle/Entity/Transport.php "Oro\Bundle\IntegrationBundle\Entity\Transport")</sup> to store paypal payment integration properties
    - Classes `PayflowExpressCheckoutConfig`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/Config/PayflowExpressCheckoutConfig.php "Oro\Bundle\PayPalBundle\Method\Config\PayflowExpressCheckoutConfig")</sup>, `PayPalPaymentsProExpressCheckoutConfig`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/Config/PayPalPaymentsProExpressCheckoutConfig.php "Oro\Bundle\PayPalBundle\Method\Config\PayPalPaymentsProExpressCheckoutConfig")</sup> were removed and instead simple parameter bag object `PayPalExpressCheckoutConfig`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/Config/PayPalExpressCheckoutConfig.php "Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfig")</sup> is being used for holding payment integration properties that are stored in PayPalSettings
    - Classes `PayflowGatewayConfig`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/Config/PayflowGatewayConfig.php "Oro\Bundle\PayPalBundle\Method\Config\PayflowGatewayConfig")</sup>, `PayPalPaymentsProConfig`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/Config/PayPalPaymentsProConfig.php "Oro\Bundle\PayPalBundle\Method\Config\PayPalPaymentsProConfig")</sup> were removed and instead simple parameter bag object `PayPalCreditCardConfig`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/Config/PayPalCreditCardConfig.php "Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig")</sup> is being used for holding payment integration properties that are stored in PayPalSettings
    - Classes `PayflowExpressCheckout`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/PayflowExpressCheckout.php "Oro\Bundle\PayPalBundle\Method\PayflowExpressCheckout")</sup>, `PayPalPaymentsProExpressCheckout`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/PayPalPaymentsProExpressCheckout.php "Oro\Bundle\PayPalBundle\Method\PayPalPaymentsProExpressCheckout")</sup> were removed and instead was added `PayPalExpressCheckoutPaymentMethod`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/PayPalExpressCheckoutPaymentMethod.php "Oro\Bundle\PayPalBundle\Method\PayPalExpressCheckoutPaymentMethod")</sup>
    - Classes `PayflowGateway`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/PayflowGateway.php "Oro\Bundle\PayPalBundle\Method\PayflowGateway")</sup>, `PayPalPaymentsPro`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/PayPalPaymentsPro.php "Oro\Bundle\PayPalBundle\Method\PayPalPaymentsPro")</sup> were removed and instead was added `PayPalCreditCardPaymentMethod`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/PayPalCreditCardPaymentMethod.php "Oro\Bundle\PayPalBundle\Method\PayPalCreditCardPaymentMethod")</sup>
    - Classes `PayflowExpressCheckout`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/View/PayflowExpressCheckout.php "Oro\Bundle\PayPalBundle\Method\View\PayflowExpressCheckout")</sup>, `PayPalPaymentsProExpressCheckout`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/View/PayPalPaymentsProExpressCheckout.php "Oro\Bundle\PayPalBundle\Method\View\PayPalPaymentsProExpressCheckout")</sup> were removed and instead was added `PayPalExpressCheckoutPaymentMethodView`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/View/PayPalExpressCheckoutPaymentMethodView.php "Oro\Bundle\PayPalBundle\Method\View\PayPalExpressCheckoutPaymentMethodView")</sup>
    - Classes `PayflowGateway`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/View/PayflowGateway.php "Oro\Bundle\PayPalBundle\Method\View\PayflowGateway")</sup>, `PayPalPaymentsPro`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/View/PayPalPaymentsPro.php "Oro\Bundle\PayPalBundle\Method\View\PayPalPaymentsPro")</sup> were removed and instead was added `PayPalCreditCardPaymentMethodView`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/View/PayPalCreditCardPaymentMethodView.php "Oro\Bundle\PayPalBundle\Method\View\PayPalCreditCardPaymentMethodView")</sup>
#### PaymentBundle
* in order to have possibility to create more than one payment method of same type PaymentBundle was significantly changed **with breaking backwards compatibility**.
    - Class `PaymentMethodRegistry`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/PaymentMethodRegistry.php "Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry")</sup> was removed, logic was moved to `PaymentMethodProvidersRegistry`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/Registry/PaymentMethodProvidersRegistry.php "Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistry")</sup> which implements `PaymentMethodProvidersRegistryInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/Registry/PaymentMethodProvidersRegistryInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface")</sup> and this registry is responsible for collecting data from all payment method providers
    - Class `PaymentMethodViewRegistry`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewRegistry.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry")</sup> was removed, logic was moved to `CompositePaymentMethodViewProvider`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/CompositePaymentMethodViewProvider.php "Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider")</sup> which implements `PaymentMethodViewProviderInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewProviderInterface.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface")</sup> this composite provider is single point to provide data from all payment method view providers
* the following classes (that are related to the actions that disable/enable
[`PaymentMethodsConfigsRule`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Entity/PaymentMethodsConfigsRule.php "Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule")) were abstracted and moved to the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle") (see the [`RuleBundle`](#RuleBundle)) section for more information):
  - [`StatusDisableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Datagrid/Extension/MassAction/Actions/StatusDisableMassAction.php "Oro\Bundle\PaymentBundle\Datagrid\Extension\MassAction\Actions\StatusDisableMassAction") (is replaced with [`StatusEnableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/Extension/MassAction/Actions/StatusEnableMassAction.php "Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle"))
  - [`StatusEnableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Datagrid/Extension/MassAction/Actions/StatusEnableMassAction.php "Oro\Bundle\PaymentBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction") (is replaced with [`StatusEnableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/Extension/MassAction/Actions/StatusEnableMassAction.php "Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle"))
  - [`StatusMassActionHandler`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Datagrid/Extension/MassAction/StatusMassActionHandler.php "Oro\Bundle\PaymentBundle\Datagrid\Extension\MassAction\StatusMassActionHandler") (is replaced with [`StatusMassActionHandler`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/Extension/MassAction/StatusMassActionHandler.php "Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\StatusMassActionHandler") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle"))
  - [`PaymentRuleActionsVisibilityProvider`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Datagrid/PaymentRuleActionsVisibilityProvider.php "Oro\Bundle\PaymentBundle\Datagrid\PaymentRuleActionsVisibilityProvider") (is replaced with [`PaymentRuleActionsVisibilityProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/PaymentRuleActionsVisibilityProvider.php "Oro\Bundle\RuleBundle\Datagrid\PaymentRuleActionsVisibilityProvider") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle"))
* the following classes (that are related to decorating [`Product`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ProductBundle/Entity/Product.php "Oro\Bundle\ProductBundle\Entity\Product") with virtual fields) were abstracted and moved to the [`ProductBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle "Oro\Bundle\ProductBundle") (see the [`ProductBundle`](#ProductBundle) section for more information):
  - [`SelectQueryConverter`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/QueryDesigner/SelectQueryConverter.php "Oro\Bundle\PaymentBundle\QueryDesigner\SelectQueryConverter") 
  - [`PaymentProductQueryDesigner`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/QueryDesigner/PaymentProductQueryDesigner.php "Oro\Bundle\PaymentBundle\QueryDesigner\PaymentProductQueryDesigner") 
  - [`ProductDecorator`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/ExpressionLanguage/ProductDecorator.php "Oro\Bundle\PaymentBundle\ExpressionLanguage\ProductDecorator")
* in order to have possibility to create more than one payment method of the same type, the PaymentBundle was significantly changed **with backward compatibility break**:
    - The [`PaymentMethodRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/PaymentMethodRegistry.php "Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry") class was replaced with the [`PaymentMethodProvidersRegistry`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/Registry/PaymentMethodProvidersRegistry.php "Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistry") which implements a [`PaymentMethodProvidersRegistryInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/Registry/PaymentMethodProvidersRegistryInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface") and this registry is responsible for collecting data from all payment method providers.
    - The [`PaymentMethodViewRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewRegistry.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry") class was replaced with the [`CompositePaymentMethodViewProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/CompositePaymentMethodViewProvider.php "Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider") which implements a [`PaymentMethodViewProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewProviderInterface.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface"). This composite provider is a single point to provide data from all payment method view providers.
#### PaymentTermBundle
* Class `Configuration`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentTermBundle/DependencyInjection/Configuration.php "Oro\Bundle\PaymentTermBundle\DependencyInjection\Configuration")</sup> is removed, `PaymentTermSettings`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentTermBundle/Entity/PaymentTermSettings.php "Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings")</sup> was created instead
* PaymentTerm implementation was changed using IntegrationBundle (refer to PaymentBundle and IntegrationBundle for details).
    - Class `Configuration`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentTermBundle/DependencyInjection/Configuration.php "Oro\Bundle\PaymentTermBundle\DependencyInjection\Configuration")</sup> was removed and instead `PaymentTermSettings`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentTermBundle/Entity/PaymentTermSettings.php "Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings")</sup> was created - entity that implements `Transport`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/IntegrationBundle/Entity/Transport.php "Oro\Bundle\IntegrationBundle\Entity\Transport")</sup> to store payment integration properties
    - Class `PaymentTermConfig`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentTermBundle/Method/Config/PaymentTermConfig.php "Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfig")</sup> was removed and instead simple parameter bag object `ParameterBagPaymentTermConfig`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentTermBundle/Method/Config/ParameterBagPaymentTermConfig.php "Oro\Bundle\PaymentTermBundle\Method\Config\ParameterBagPaymentTermConfig")</sup> is being used for holding payment integration properties that are stored in PaymentTermSettings
#### PricingBundle
* class `PriceListConfigConverter`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PricingBundle/SystemConfig/PriceListConfigConverter.php "Oro\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter")</sup> changes:
    - constant `PRIORITY_KEY` was removed, use `SORT_ORDER_KEY` instead
* class `BasePriceListRelation`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PricingBundle/Entity/BasePriceListRelation.php "Oro\Bundle\PricingBundle\Entity\BasePriceListRelation")</sup> changes:
    - property `$priority` was removed, use `$sortOrder` instead
    - methods `getPriority` and `setPriority` were removed, use `getSortOrder` and `setSortOrder` instead accordingly
* class `PriceListConfig`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PricingBundle/SystemConfig/PriceListConfig.php "Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig")</sup> changes:
    - property `$priority` was removed, use `$sortOrder` instead
    - methods `getPriority` and `setPriority` were removed, use `getSortOrder` and `setSortOrder` instead accordingly
* interface `PriceListAwareInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PricingBundle/Entity/PriceListAwareInterface.php "Oro\Bundle\PricingBundle\Entity\PriceListAwareInterface")</sup> changes:
    - method `getPriority` was removed, use `getSortOrder` instead
* class `PriceListSelectWithPriorityType`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PricingBundle/Form/Type/PriceListSelectWithPriorityType.php "Oro\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType")</sup> changes:
    - field `priority` was removed. Field `_position` from `SortableExtension`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/FormBundle/Form/Extension/SortableExtension.php "Oro\Bundle\FormBundle\Form\Extension\SortableExtension")</sup> is used instead.
#### ProductBundle
* removed constructor of `ProductPageVariantType`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/Form/Type/ProductPageVariantType.php "Oro\Bundle\ProductBundle\Form\Type\ProductPageVariantType")</sup>.
    - corresponding logic moved to `PageVariantTypeExtension`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Form/Extension/PageVariantTypeExtension.php "Oro\Bundle\WebCatalogBundle\Form\Extension\PageVariantTypeExtension")</sup>
#### RedirectBundle
* removed property `website` in favour of `scopes` collection using  from `Redirect`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RedirectBundle/Entity/Redirect.php "Oro\Bundle\RedirectBundle\Entity\Redirect")</sup> class
#### ShippingBundle
* the following classes that are related to decorating [`Product`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ProductBundle/Entity/Product.php "Oro\Bundle\ProductBundle\Entity\Product") with virtual fields) were abstracted and moved to the [`ProductBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle "Oro\Bundle\ProductBundle") (see the [`ProductBundle`](#ProductBundle) section for more information):
  - [`SelectQueryConverter`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/QueryDesigner/SelectQueryConverter.php "Oro\Bundle\ShippingBundle\QueryDesigner\SelectQueryConverter") 
  - [`ShippingProductQueryDesigner`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/QueryDesigner/ShippingProductQueryDesigner.php "Oro\Bundle\ShippingBundle\QueryDesigner\ShippingProductQueryDesigner") 
  - [`ProductDecorator`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/ExpressionLanguage/ProductDecorator.php "Oro\Bundle\ShippingBundle\ExpressionLanguage\ProductDecorator")
  - In the [`DecoratedProductLineItemFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/ExpressionLanguage/DecoratedProductLineItemFactory.php "Oro\Bundle\ShippingBundle\ExpressionLanguage\DecoratedProductLineItemFactory") class, the only dependency is now 
[`VirtualFieldsProductDecoratorFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/VirtualFields/VirtualFieldsProductDecoratorFactory.php "Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory").
* the classes that are related to actions that disable/enable [`ShippingMethodsConfigsRule`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Entity/ShippingMethodsConfigsRule.php "Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule") were abstracted and moved to the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle") (see the [`RuleBundle`](#RuleBundle)) section for more information):
  - Removed [`StatusDisableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Datagrid/Extension/MassAction/Actions/StatusDisableMassAction.php "Oro\Bundle\ShippingBundle\Datagrid\Extension\MassAction\Actions\StatusDisableMassAction") and switched definition to [`StatusEnableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/Extension/MassAction/Actions/StatusEnableMassAction.php "Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle")
  - [`StatusEnableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Datagrid/Extension/MassAction/Actions/StatusEnableMassAction.php "Oro\Bundle\ShippingBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction") and switched definition to [`StatusEnableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/Extension/MassAction/Actions/StatusEnableMassAction.php "Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle")
  - [`StatusMassActionHandler`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Datagrid/Extension/MassAction/StatusMassActionHandler.php "Oro\Bundle\ShippingBundle\Datagrid\Extension\MassAction\StatusMassActionHandler") and switched definition to [`StatusMassActionHandler`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/Extension/MassAction/StatusMassActionHandler.php "Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\StatusMassActionHandler") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle")
  - [`ShippingRuleActionsVisibilityProvider`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Datagrid/ShippingRuleActionsVisibilityProvider.php "Oro\Bundle\ShippingBundle\Datagrid\ShippingRuleActionsVisibilityProvider") and switched definition to [`RuleActionsVisibilityProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/RuleActionsVisibilityProvider.php "Oro\Bundle\RuleBundle\Datagrid\RuleActionsVisibilityProvider") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle")
#### UPSBundle
* the class [`InvalidateCacheScheduleCommand`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Command/InvalidateCacheScheduleCommand.php "Oro\Bundle\UPSBundle\Command\InvalidateCacheScheduleCommand") was removed, [`InvalidateCacheScheduleCommand`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CacheBundle/Command/InvalidateCacheScheduleCommand.php "Oro\Bundle\CacheBundle\Command\InvalidateCacheScheduleCommand") should be used instead
* the class [`InvalidateCacheAtHandler`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Handler/InvalidateCacheAtHandler.php "Oro\Bundle\UPSBundle\Handler\InvalidateCacheAtHandler") was removed, [`InvalidateCacheActionHandler`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Handler/InvalidateCacheActionHandler.php "Oro\Bundle\UPSBundle\Handler\InvalidateCacheActionHandler") should be used instead
* resource [`invalidateCache.html.twig`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Resources/views/Action/invalidateCache.html.twig "Oro\Bundle\UPSBundle\Resources\views\Action\invalidateCache.html.twig") was removed, use corresponding resource from CacheBundle
* resource [`invalidate-cache-button-component.js`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Resources/public/js/app/components/invalidate-cache-button-component.js "Oro\Bundle\UPSBundle\Resources\public\js\app\components\invalidate-cache-button-component.js") was removed , use corresponding resource from CacheBundle
#### WebsiteBundle
* the `protected $websiteManager` property was removed from [`OroWebsiteExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebsiteBundle/Twig/OroWebsiteExtension.php "Oro\Bundle\WebsiteBundle\Twig\OroWebsiteExtension")
* the `protected $websiteUrlResolver` property was removed from [`WebsitePathExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebsiteBundle/Twig/WebsitePathExtension.php "Oro\Bundle\WebsiteBundle\Twig\WebsitePathExtension")
#### WebsiteSearchBundle
* the following method [`IndexationRequestListener::getEntitiesWithUpdatedIndexedFields`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/WebsiteSearchBundle/EventListener/IndexationRequestListener.php "Oro\Bundle\WebsiteSearchBundle\EventListener\IndexationRequestListener") was removed 
