The upgrade instructions are available at [Oro documentation website](https://doc.oroinc.com/master/backend/setup/upgrade-to-new-version/).

The current file describes significant changes in the code that may affect the upgrade of your customizations.

## Changes in the Сommerce package versions

- [5.1.0](#510-2023-03-31)
- [5.0.0](#500-2022-01-26)
- [4.2.3](#423)
- [4.2.2](#422)
- [4.2.0](#420-2020-01-29)
- [4.1.0](#410-2020-01-31)
- [4.0.0](#400-2019-07-31)
- [3.1.2](#312-2019-02-05)
- [3.1.0](#310-2019-01-30)
- [3.0.0](#300-2018-07-27)
- [1.6.0](#160-2018-01-31)
- [1.5.0](#150-2017-11-30)
- [1.4.0](#140-2017-09-29)
- [1.3.0](#130-2017-07-28)
- [1.2.0](#120-2017-06-01)
- [1.1.0](#110-2017-03-31)


## 5.1.0 (2023-03-31)
[Show detailed list of changes](incompatibilities-5-1.md)

### Added

#### WebCatalogBundle
* Added `\Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Loader\ResolvedContentNodesLoader`, `\Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Loader\ResolvedContentVariantsLoader` to easily get resolved content nodes for specified content node ids.
* Added `\Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Factory\ResolvedContentNodeFactory`, `\Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Factory\ResolvedContentVariantFactory` to easily create `\Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentNode` and `\Oro\Bundle\WebCatalogBundle\Cache\ResolvedData\ResolvedContentVariant` models.
* Added `\Oro\Bundle\WebCatalogBundle\Cache\ResolvedContentNodeNormalizer` to decrease the complexity of `\Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCache`.
* Added `\Oro\Bundle\WebCatalogBundle\ContentNodeUtils\Loader\ResolvedContentNodesLoader` to decrease the complexity of `\Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolver`.
* Added the ability to specify multiple scopes in `\Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeTreeResolverInterface::getResolvedContentNode`.
* Added `\Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeMergingResolver` that merges resolved content nodes from multiple scopes.
* Added `\Oro\Bundle\WebCatalogBundle\Cache\ResolvedContentNodesMerger` to decrease the complexity of `\Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeMergingResolver`.
* Added `\Oro\Bundle\WebCatalogBundle\Menu\MenuContentNodesProviderInterface`, `\Oro\Bundle\WebCatalogBundle\Menu\MenuContentNodesProvider`, `\Oro\Bundle\WebCatalogBundle\Menu\StorefrontMenuContentNodesProvider` and `\Oro\Bundle\WebCatalogBundle\Menu\CompositeMenuContentNodesProvider` to provide an ability of getting resolved content nodes for showing in menu.

#### PricingBundle
* Added Organization ownership type to the `Oro\Bundle\PricingBundle\Entity\PriceList` entity. All existing prices was moved to the first organization. 

#### CatalogBundle
* Category. Added sort order management for Products in categories:
    - New field `category_sort_order` in Product entity & website search index to manage sort orders in Categories
    - New input fields in Category edition grid in backend
    - New default ordering behaviour for related frontend grids  (`frontend-product-search-grid` based on Category)
* Added `\Oro\Bundle\CatalogBundle\Menu\MenuCategoriesProviderInterface`, `\Oro\Bundle\CatalogBundle\Menu\MenuCategoriesProvider`, `\Oro\Bundle\CatalogBundle\Menu\MenuCategoriesCachingProvider` to provide an ability of getting categories data for showing in menu.
* Added `\Oro\Bundle\CatalogBundle\Menu\MenuCategoriesCache` that encapsulates the normalization logic of categories data.
* Added "category" field to `\Oro\Bundle\CommerceMenuBundle\Entity\MenuUpdate`.


#### CMSBundle
* WYSIWYG editor.
    - Added a new control option to add/remove cells and rows in the table.
    - Added the `renderWysiwygContent` TWIG macro and a layout block type `wysiwyg_content` for rendering WYSIWYG content on the storefront. See the [How to Display WYSIWYG Field](https://doc.oroinc.com/bundles/commerce/CMSBundle/WYSIWYG-field/how-to-display-wysiwyg-field/) article for more information.

* Added entity name provider for the `Page` entity.

#### ConsentBundle
* Added an entity name provider for the `Consent` entity.
* Added `renderWysiwygContent` TWIG macro and a layout block type `wysiwyg_content` for rendering WYSIWYG content on storefront.
  See article [How to Display WYSIWYG Field](https://doc.oroinc.com/bundles/commerce/CMSBundle/WYSIWYG-field/how-to-display-wysiwyg-field/)
  for more information.
* Updated WYSIWYG editor to v0.20.1.
* Added the possibility to define a model and a view for WYSIWYG component types with a function and an object.

#### ProductBundle
* The `Brand` entity now has its own search result template for the backend search
* Added the `product_original_filenames` feature. This feature is enabled when `oro_attachment.original_file_names_enabled`.
  is disabled and `oro_product.original_file_names_enabled` is enabled.

* The `relevance_weight` field is added to the website search mapping for the Product entity.

* Product Collections. Added sort order management for Products in Product Collections:
    - New entity `ProductCollectionSortOrder` and website search field `assigned_to_sort_order.ASSIGN_TYPE_ASSIGN_ID` have been added to link Products to Segments with a SortOrder to manage sort orders in Product Collections
    - New default ordering behaviour added for related frontend grids (`frontend-product-search-grid` based on ProductCollection)

#### PromotionBundle
* Added entity name provider for the `Promotion` entity.

#### SEOBundle
* Enabled the `orphanRemoval` option for SEO fields: metaDescriptions, metaKeywords, metaTitles.


#### WebCatalogBundle
* ProductCollection ContentVariant. Added sort order management for Products in categories
    - New input fields in ProductCollection ContentVariant edition grid

### Changed

#### ShippingBundle
* Added strict types to `Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface` and all classes that implement this interface. 
* Added strict types to `Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface` and all classes that implement this interface. 
* Added strict types to `Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface` and all classes that implement this interface.
* Added strict types to `Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface` and all classes that implement this interface.
* Removed unneeded classes that implement `Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface` and replace them with `Oro\Bundle\ShippingBundle\Method\Provider\Integration\ChannelShippingMethodProvider`.

#### WebCatalogBundle
* Changed `\Oro\Bundle\WebCatalogBundle\Async\WebCatalogCacheProcessor` so it builds cache starting always from the root content node.
* Changed `\Oro\Bundle\WebCatalogBundle\Provider\ContentNodeProvider::getContentVariantIds` so the ordering of the loaded data follows the order of specified ids.

#### CMSBundle
* Update Grapesjs to 0.19.5 version
* Changed the base-type component. Changed `modelMixin` to `modelProps` and `viewMixin` to `viewProps`.  An object definition of the editor type model/view was passed.
  Added `ModelType` and ViewType properties to pass the constructor function
* Changed component types naming from `text-type-builder.js` to `text-type.js`. Removed `-builder` in file names

#### InventoryBundle

* Inventory status website search index field has been renamed from `inventory_status` to `inv_status`
  to avoid collision with the field name for the inventory status product attribute
* Inventory website search index fields (inventory status, low inventory threshold, is upcoming,
  availability date) have been moved to the separate `inventory` indexation group

#### PricingBundle

* Price index fields have been renamed (pay attention to a dot notation):
  `minimal_price_CPL_ID_CURRENCY_UNIT` to `minimal_price.CPL_ID_CURRENCY_UNIT`,
  `minimal_price_CPL_ID_CURRENCY` to `minimal_price.CPL_ID_CURRENCY`,
  `minimal_price_PRICE_LIST_ID_CURRENCY_UNIT` to `minimal_price.PRICE_LIST_ID_CURRENCY_UNIT`,
  `minimal_price_PRICE_LIST_ID_CURRENCY` to `minimal_price.PRICE_LIST_ID_CURRENCY`

#### ProductBundle
* Product search index field `product_id` has been replaced with `system_entity_id`
* `Oro\Bundle\ProductBundle\Provider\ProductImageFileNameProvider` is applicable if `product_original_filenames` feature is enabled.
* Storefront product autocomplete now includes list of categories with found products
* ProcessAutocompleteDataEvent data format has been changed, now it includes full autocomplete data: products, categories, and total count

#### SearchBundle
* Changed website search engine configuration: `website_search_engine_dsn` parameter is used instead of `search_engine_name`, `search_engine_host`, `search_engine_port`, `search_engine_index_prefix`, `search_engine_username`, `search_engine_password`, `search_engine_ssl_verification`, `search_engine_ssl_cert`,  `search_engine_ssl_cert_password`, `search_engine_ssl_key`, `search_engine_ssl_key_password`, `website_search_engine_index_prefix`.
* Separate setup via dedicated DSN-s allows splitting search engine's connections between back-office and storefront.

### Removed

#### ShippingBundle
* Removed unneeded `Oro\Bundle\ShippingBundle\Provider\ShippingMethodChoicesProviderInterface`.

#### WebCatalogBundle
* Removed block type `menu_item`; It was updated and moved to `CommerceMenuBundle`
* Removed `\Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCache::deleteForNode`, the method is moved to `\Oro\Bundle\WebCatalogBundle\Async\ContentNodeSlugsProcessor`. 
* Removed `Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeResolver`. New resolvers are used instead - `\Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeCachingResolver` and `\Oro\Bundle\WebCatalogBundle\Cache\ContentNodeTreeMergingResolver`.

#### CatalogBundle
* Removed block type `category_list`
* Removed `\Oro\Bundle\CatalogBundle\Layout\DataProvider\DTO\Category`, `\Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider::getCategoryTree`, `\Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider::getCategoryTreeArray`, `\Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider::getRootCategory` as not needed for building a menu anymore. Instead, the `\Oro\Bundle\CatalogBundle\Menu\MenuCategoriesCachingProvider` is used.

#### CMSBundle
* Removed `text_with_placeholders`, `wysiwyg_style` layout block types. Use `wysiwyg_content` instead.
* Removed app module `grapesjs-module.js`.

#### OrderBundle
* Listener `oro_order.event_listener.frontend_order_datagrid` is removed. Its responsibility is merged
  into `oro_order.event_listener.order_datagrid`.


## 5.0.0 (2022-01-26)
[Show detailed list of changes](incompatibilities-5-0.md)


### Added

#### CheckoutBundle
* Added a few mediator events for the Single Page Checkout

##### orocheckout/js/app/components/single-page-checkout-component

`single-page-checkout:before-layout-subtree-content-loading` - triggered before the update of Layout Subtrees

`single-page-checkout:after-layout-subtree-content-loading` - triggered after the update of Layout Subtrees

`single-page-checkout:layout-subtree-content-loading-fail` - triggered when there was an error and the response failed

##### orocheckout/js/app/views/single-page-checkout-form-view

`single-page-checkout:rendered` - triggered when the form has rendered

`single-page-checkout:before-change` - triggered when the form changes

`single-page-checkout:after-change` - triggered after the form changes

`single-page-checkout:before-force-change` - triggered before the forced form change

`single-page-checkout:after-force-change` - triggered after the forced form change

#### CMSBundle
* Created `optimized` layout theme with `landing` extra js build utilized on oro_cms_frontend_page_view page, see article [How to Create Extra JS Build for a Landing Page](https://doc.oroinc.com/master/frontend/storefront/how-to/how-to-create-extra-js-build-for-landing-page/).  



### Changed
#### CatalogBundle
* Website search field `category_path_CATEGORY_PATH` has been renamed to `category_paths.CATEGORY_PATH`

#### ProductBundle
* Website search field `assigned_to_ASSIGN_TYPE_ASSIGN_ID` has been renamed to `assigned_to.ASSIGN_TYPE_ASSIGN_ID.CATEGORY_PATH`
* Website search field `manually_added_to_ASSIGN_TYPE_ASSIGN_ID` has been renamed to `manually_added_to.ASSIGN_TYPE_ASSIGN_ID`
* In order to improve page performance, some JS-components within product item blocks are initialized only on `click` and `focusin` DOM-events. See  [Initialize Components on DOM events](https://doc.oroinc.com/frontend/javascript/page-component/#initialize-components-on-dom-events)
* Changes in `/admin/api/files/{id}` REST API resource:
    - the attribute `filePath` structure was updated from **\["product_original": "/path/to/image.jpeg"\]** to **\[{"url": "/path/to/image.jpeg", "dimension": "product_original"}\]**

#### ShoppingListBundle
* The hydration of Product entities the `frontend-product-search-grid` datagrid was removed for simple products.
* The datagrid extension `Oro\Bundle\ShoppingListBundle\Datagrid\Extension\FrontendMatrixProductGridExtension`
  (service ID: `oro_shopping_list.datagrid.extension.frontend_product_grid`) was replaced with datagrid listener
  `Oro\Bundle\ShoppingListBundle\Datagrid\EventListener\FrontendMatrixProductGridEventListener`
  (service ID: `oro_shopping_list.datagrid.event_listener.frontend_matrix_product_grid`)

#### VisibilityBundle
* Website search field `visibility_customer_CUSTOMER_ID` has been renamed to `visibility_customer.CUSTOMER_ID`

#### WebsiteSearchBundle
* Changed `oro_website_search.event_listener.orm.fulltext_index_listener` to use `doctrine.dbal.search_connection`
* Changed `oro_website_search.fulltext_index_manager` to use `doctrine.dbal.search_connection`

### Removed
#### ApplicationBundle
* Removed all deprecated code intended to run multiple Symfony applications on the same codebase.

#### WebsiteSearchBundle
* Removed `oro_website_search.tests.disable_listeners_for_data_fixtures`, listeners disabled by default during fixtures loading.

## 4.2.3

### Changed

#### PricingBundle
* TEMPORARY (PostgreSQL)/CREATE TEMPORARY TABLES (MySQL) database privilege became required

## 4.2.2

### Changed

#### OrderBundle
* The format of the value for the `percent` field of the `orderdiscounts` API resource was changed.
  From now a percentage value is not multiplied by 100. It means that from now 100% is 1, not 100.

## 4.2.0 (2020-01-29)
[Show detailed list of changes](incompatibilities-4-2.md)

### Added

#### CheckoutBundle
* Added the `oro_checkout.checkout_max_line_items_per_page` option to the system configuration.
* Added the following events:
    - `Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionBeforeEvent` - dispatched before a checkout transition is started, contains workflow item and the transition.
    - `Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionAfterEvent` - dispatched after a checkout transition is finished, contains workflow item, the transition, `isAllowed` flag and collected errors if any.
* Added `is_checkout_state_valid` condition. This condition compares a saved checkout state (retrieved by the provided token) to the current checkout state.
* Added method `Oro\Bundle\CheckoutBundle\WorkflowState\Handler\CheckoutErrorHandler::getWorkflowErrors()`. The method returns workflow-related errors from FromErrorIterator.

### Changed

#### AlternativeCheckoutBundle
* OroAlternativeCheckoutBundle has been moved from oro/commerce package to oro/commerce-demo-checkouts package.

#### CheckoutBundle
* Added the following datagrids:
    - `frontend-checkout-line-items-grid`
    - `frontend-single-page-checkout-line-items-grid`.
  For more details on datagrid customizations please see the [datagrid documentation](https://doc.oroinc.com/backend/entities/customize-datagrids/)
  
#### PricingBundle
* `oropricing/js/app/views/quick-add-item-price-view` js module is re-developed and renamed to `oropricing/js/app/views/quick-add-row-price-view`

#### ProductBundle
* The message queue topic `imageResize` was renamed to `oro_product.image_resize`.
* Functionality of Quick Order Form is re-developed
* `oroproduct/js/app/components/quick-add-copy-paste-form-component` js module is re-developed and renamed to `oroproduct/js/app/views/quick-add-copy-paste-form-view`
* `oroproduct/js/app/views/quick-add-item-view` js module is re-developed and renamed to `oroproduct/js/app/views/quick-add-row-view`
* `oroproduct/js/app/views/quick-add-view` js module is re-developed and renamed to `oroproduct/js/app/views/quick-order-form-view`
* js mediator events `autocomplete:productFound` `autocomplete:productNotFound` are replaced with custom DOM events

#### PromotionBundle
* The service `Oro\Bundle\PromotionBundle\Executor\PromotionExecutor` can be used without caching processed DiscountContext. Please refer to `oro_promotion.promotion_executor` and `oro_promotion.shipping_promotion_executor` examples.

#### RFPBundle
* The name for `/admin/api/requests` REST API resource was changed to `/admin/api/rfqs`.
* The name for `/admin/api/requestproducts` REST API resource was changed to `/admin/api/rfqproducts`.
* The name for `/admin/api/requestproductitems` REST API resource was changed to `/admin/api/rfqproductitems`.
* The name for `/admin/api/requestadditionalnotes` REST API resource was changed to `/admin/api/rfqadditionalnotes`.
* The name for `/admin/api/rfpcustomerstatuses` REST API resource was changed to `/admin/api/rfqcustomerstatuses`.
* The name for `/admin/api/rfpinternalstatuses` REST API resource was changed to `/admin/api/rfqinternalstatuses`.

#### ShippingBundle
* The method `Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository::findByProductsAndUnits()` was renamed to  `Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository::findIndexedByProductsAndUnits()` and now uses a plain DQL query without entity hydration.

#### ShoppingListBundle
* The shopping list page has been completely redesigned. Removed all layout config, styles, javascript, translations,
etc. related to the old design.

#### TaxBundle
* Order tax recalculation check is not used on storefront.
* Changing the Order currency will no longer cause taxes to be recalculated, because changing the Order currency is not supported.

#### WebsiteSearchBundle
* `oro_website_search.event.website_search_mapping.configuration` event dispatches
with `Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent` event class
that have configuration loaded from config files. So event have full access to configuration. 
* The merge of website search mapping config after the `oro_website_search.event.website_search_mapping.configuration` event
was dispatched has been removed. At listeners please add full configuration that do not need additional processing with config processor. 

### Removed

* Removed long-unused `oro_customer_menu` layout import from all bundles.

#### CheckoutBundle
* Removed duplicated workflow preconditions/conditions checks.
* Removed duplicated checkout state generations and checks.
* Removed `order_line_item_has_count` condition.
* Removed the following layout block types:
    - `checkout_order_summary_line_items`

#### PaymentBundle
* Method `Oro\Bundle\PaymentBundle\ExpressionLanguage\DecoratedProductLineItemFactory::createLineItemWithDecoratedProductByLineItem()` is removed, use `Oro\Bundle\PaymentBundle\ExpressionLanguage\DecoratedProductLineItemFactory::createPaymentLineItemWithDecoratedProduct()` instead.

#### ProductBundle
* Method `Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory::createDecoratedProductByProductHolders()` is removed, use `Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory::createDecoratedProduct()` instead.
* Removed the `oro_product.matrix_form_on_shopping_list` option from the system configuration.

#### PricingBundle
* The `unique_job_slug` option was removed during sending the import price list MQ message. 

#### ShoppingListBundle
* Method `Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository::findDuplicate()` is removed, use
`Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository::findDuplicateInShoppingList()` instead.
* Methods `Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository::deleteItemsByShoppingListAndInventoryStatuses()`,
	`Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository::deleteDisabledItemsByShoppingList()` are
	removed, use `Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository::deleteNotAllowedLineItemsFromShoppingList()`
	instead.
* Removed the following layout block types:
    - `shopping_list_owner_select_block`
    - `shopping_list_line_items_list`
    - `shopping_lists_menu`
* Removed the following layout data providers:
    - `oro_shopping_list_product_unit_code_visibility`
    - `shopping_list_form_availability_provider`
    - `oro_shopping_list_matrix_grid_shopping_list`
    - `oro_shopping_list_products_units`
* Removed the `oro_shoppinglist_frontend_duplicate` operation. The `oro_shoppinglist_frontend_duplicate_action` operation is now used.

#### WebsiteSearchBundle

* Removed `Oro\Bundle\WebsiteSearchBundle\Event\WebsiteSearchMappingEvent` event class and used 
`Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent` class instead of.
* Removed `Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider` and used
`\Oro\Bundle\SearchBundle\Provider\SearchMappingProvider` class instead of.



## 4.1.0 (2020-01-31)
[Show detailed list of changes](incompatibilities-4-1.md)

### Added
#### CMSBundle
* Added *WYSIWYG* field to Entity Manager. Read more in documentation [how to change TextArea field to WYSIWYG field](https://doc.oroinc.com/master/backend/bundles/commerce/CMSBundle/WYSIWYG-field/how-to-change-textarea-field-to-wysiwyg-field/)


### Changed
#### WebCatalog component
* Methods `getApiResourceClassName()` and `getApiResourceIdentifierDqlExpression()` were added to
  `Oro\Bundle\WebCatalogBundle\ContentVariantType\SystemPageContentVariantType`.
  
#### CMSBundle
* A new "home-page-slider" content widget is added which makes possible to dynamically modify slider settings as well
as content of each slide. If you install application from a scratch new slider will be available out of the box. But
you should consider to upgrade custom slider while application update. For this you need to modify "home-page-slider"
widget to have same look as old one. And replace content of "home-page-slider" content block to 
"<div data-title="home-page-slider" data-type="image_slider" class="content-widget content-placeholder">{{ widget("home-page-slider") }}</div>".
It will render slider via widget.

#### WebCatalogBundle
* The `_web_content_scope` request attribute was removed.
  Use `Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentScopeProvider` to get the current scope.
  This class loads the scope on demand.
* The `_content_variant` request attribute was removed.
  Use `Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentVariantProvider` to get the current content variant.
  This class loads the content variant on demand.

#### WebCatalogBundle
* The `current_website` request attribute was removed.
  To get the current website from HTTP request `Oro\Bundle\WebsiteBundle\Provider\RequestWebsiteProvider` was added.
  This class loads the website on demand.
  
### Removed

* `*.class` parameters for all entities were removed from the dependency injection container.
The entity class names should be used directly, e.g. `'Oro\Bundle\EmailBundle\Entity\Email'`
instead of `'%oro_email.email.entity.class%'` (in service definitions, datagrid config files, placeholders, etc.), and
`\Oro\Bundle\EmailBundle\Entity\Email::class` instead of `$container->getParameter('oro_email.email.entity.class')`
(in PHP code).

#### Config component
* The trait `Oro\Component\Cache\Layout\DataProviderCacheTrait` was removed as it added additional complexity
  to cacheable layout data providers instead of simplify them.
* The unneeded class `Oro\Component\Cache\Layout\DataProviderCacheCleaner` was removed.

#### PricingBundle
* The `getName()` method was removed from `Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface`.
  Use the `alias` attribute of the `oro_pricing.subtotal_provider` DIC tag instead.

#### PromotionBundle
* The handling of `priority` attribute for `oro_promotion.discount_context_converter`,
  `oro_promotion.promotion_context_converter` and `oro_promotion.discount_strategy` DIC tags
  was changed to correspond Symfony recommendations.
  If you have services with these tags, change the sign of the priority value for them.
  E.g. `{ name: oro_promotion.discount_context_converter, priority: 100 }` should be changed to
  `{ name: oro_promotion.discount_context_converter, priority: -100 }`

#### TaxBundle
* The `getProcessingClassName()` method was removed from `Oro\Bundle\TaxBundle\Mapper\TaxMapperInterface`.
  Use the `class` attribute of the `oro_tax.tax_mapper` DIC tag instead.
* The `getName()` method was removed from `Oro\Bundle\TaxBundle\Provider\TaxProviderInterface`.
  Use the `alias` attribute of the `oro_tax.tax_provider` DIC tag instead.



## 4.0.0 (2019-07-31)
[Show detailed list of changes](incompatibilities-4-0.md)

### Changed

#### PaymentBundle
* In `Oro\Bundle\PaymentBundle\Controller\Api\Rest\PaymentMethodsConfigsRuleController::enableAction` 
 (`/paymentrules/{id}/enable` path)
 action the request method was changed to POST. 
* In `Oro\Bundle\PaymentBundle\Controller\Api\Rest\PaymentMethodsConfigsRuleController::disableAction` 
 (`/paymentrules/{id}/disable` path)
 action the request method was changed to POST.
#### PricingBundle
* In `Oro\Bundle\PricingBundle\Controller\AjaxPriceListController::defaultAction` 
 (`oro_pricing_price_list_default` route)
 action the request method was changed to POST.
* In `Oro\Bundle\PricingBundle\Controller\AjaxProductPriceController::deleteAction` 
 (`oro_pricing_price_list_default` route)
 action the request method was changed to DELETE.
* Introduced concept of import/export owner. Applied approach with role-based owner-based permissions to the export and import functionality.
* Option `--email` has become required for `oro:import:price-list:file` command.
* `Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchAttributeTypeInterface`:
 	- all methods from the removed `Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchableAttributeTypeInterface` except `getFilterStorageFieldType` and `getFilterableFieldName` are moved to this interface.
 
 #### SaleBundle
 * In `Oro\Bundle\SaleBundle\Controller\AjaxQuoteController::entryPointAction` 
  (`oro_quote_entry_point` route)
  action the request method was changed to POST.
 #### ShippingBundle
 * In `Oro\Bundle\ShippingBundle\Controller\Api\Rest\ShippingMethodsConfigsRuleController::enableAction` 
  (`/shippingrules/{id}/enable` path)
  action the request method was changed to POST.
 * In `Oro\Bundle\ShippingBundle\Controller\Api\Rest\ShippingMethodsConfigsRuleController::disableAction` 
  (`/shippingrules/{id}/disable` path)
  action the request method was changed to POST.
 
#### ShoppingListBundle

* The `removeProductFromViewAction` in `Oro\Bundle\ShoppingListBundle\Controller\Frontend\AjaxLineItemController` (`oro_shopping_list_frontend_remove_product` route) now support only `DELETE` method insteadof `POST`.
* In `Oro\Bundle\ShoppingListBundle\Controller\Frontend\AjaxLineItemController::addProductFromViewAction` 
 (`oro_shopping_list_frontend_add_product` route)
 action the request method was changed to POST.

### Removed
#### PaymentBundle
 * Event `oro_payment.event.extract_line_item_options` will no longer be dispatched. Implementations of `Oro\Bundle\PayPalBundle\OptionsProvider\OptionsProviderInterface` will be used instead.
 * Event `oro_payment.event.extract_address_options` will no longer be dispatched. Class `PaymentOrderShippingAddressOptionsProvider` will be used instead.

#### WebsiteSearchBundle
* Service `oro_website_search.async_messaging.search_message.processor.job_runner` was removed, that trigger duplicated messages to the message queue with topics:
    - `oro.website.search.indexer.save`
    - `oro.website.search.indexer.delete`
    - `oro.website.search.indexer.reset_index`
    - `oro.website.search.indexer.reindex`

## 3.1.2 (2019-02-05)

## 3.1.0 (2019-01-30)
[Show detailed list of changes](incompatibilities-3-1.md)

### Changed
#### OrderBundle
* Changes in `/admin/api/orderaddresses` REST API resource:
    - the attribute `created` was renamed to `createdAt`
    - the attribute `updated` was renamed to `updatedAt`
#### ShoppingListBundle
* Functionality related to the currently active shopping list was moved from `Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager` to `Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager`. The service id for the CurrentShoppingListManager is `oro_shopping_list.manager.current_shopping_list`.
* Service `oro_shopping_list.shopping_list.manager` was renamed to `oro_shopping_list.manager.shopping_list`.


## 3.0.0 (2018-07-27)
[Show detailed list of changes](incompatibilities-3-0.md)

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
* Now enabled tax provider in system config is a main point for tax calculation instead of TaxManager (look at the TaxProviderInterface). Read more in documentation [how to setup custom tax provider](https://github.com/oroinc/orocommerce/tree/1.6/src/Oro/Bundle/TaxBundle#create-custom-tax-provider).

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
* Added Previously purchased products functionality. [Documentation](https://github.com/oroinc/orocommerce/blob/1.6/src/Oro/Bundle/OrderBundle/Resources/doc/previously-purchased-products.md)
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
* Added Low Inventory Highlights functionality.[Documentation](https://github.com/oroinc/orocommerce/blob/1.6/src/Oro/Bundle/InventoryBundle/Resources/doc/low_inventory_highlights.md)

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
* Form type `OrderDiscountItemsCollectionType` and related `oroorder/js/app/views/discount-items-view` JS view were removed, new `OrderDiscountCollectionTableType`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/OrderBundle/Form/Type/OrderDiscountCollectionTableType.php "Oro\Bundle\OrderBundle\Form\Type\OrderDiscountCollectionTableType")</sup> and `oroorder/js/app/views/discount-collection-view` are introduced.
#### PromotionBundle
* Class `AppliedDiscountManager`
    * class removed, logic was moved to `AppliedPromotionManager`
    * service of this manager removed, new `oro_promotion.applied_promotion_manager` service  was created
#### RedirectBundle
* Class `Router`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.4.0/src/Oro/Bundle/RedirectBundle/Routing/Router.php "Oro\Bundle\RedirectBundle\Routing\Router")</sup>
    * removed method `setFrontendHelper`, `setMatchedUrlDecisionMaker` method added instead.

## 1.3.0 (2017-07-28)
[Show detailed list of changes](incompatibilities-1-3.md)

### Added
#### CronBundle
* new collection form type for schedule intervals was added `ScheduleIntervalsCollectionType`
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
    - `UpdateLexemesOnPriceRuleDeleteListProcessor`<sup>[[?]](https://github.com/oroinc/orocommerce/blob/1.3.0/src/Oro/Bundle/PricingBundle/Api/Processor/UpdateLexemesOnPriceRuleDeleteListProcessor.php "Oro\Bundle\PricingBundle\Api\UpdateLexemesOnPriceRuleDeleteListProcessor")</sup> to update price rule lexemes while deleting list of price rules
    - `UpdateLexemesOnPriceRuleDeleteProcessor`<sup>[[?]](https://github.com/oroinc/orocommerce/blob/1.3.0/src/Oro/Bundle/PricingBundle/Api/Processor/UpdateLexemesOnPriceRuleDeleteProcessor.php "Oro\Bundle\PricingBundle\Api\UpdateLexemesOnPriceRuleDeleteProcessor")</sup> to update price rule lexemes while deleting single price rule
    - `UpdateLexemesPriceRuleProcessor`<sup>[[?]](https://github.com/oroinc/orocommerce/blob/1.3.0/src/Oro/Bundle/PricingBundle/Api/Processor/UpdateLexemesPriceRuleProcessor.php "Oro\Bundle\PricingBundle\Api\UpdateLexemesPriceRuleProcessor")</sup> to update price rule lexemes while saving price rule
    - `PriceListRelationTriggerHandlerForWebsiteAndCustomerProcessor` to rebuild price lists when customer aware relational entities are modified
    - `PriceListRelationTriggerHandlerForWebsiteAndCustomerGroupProcessor` to rebuild price lists when customer group aware relational entities are modified
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
* added `DefaultWebsiteSubscriber` to set Default website when not provided on form.
### Changed
#### AuthorizeNetBundle
* AuthorizeNetBundle extracted to individual package. See [https://github.com/orocommerce/OroAuthorizeNetBundle](https://github.com/orocommerce/OroAuthorizeNetBundle) for details.
#### InventoryBundle
* inventory API has changed. Please, see [documentation](https://github.com/oroinc/orocommerce/blob/1.3.0/src/Oro/Bundle/InventoryBundle/Resources/doc/api/inventory-level.md) for more information.
#### OrderBundle
* return value of method `Oro\Bundle\OrderBundle\Manager\AbstractAddressManager:getGroupedAddresses` changed from `array` to `TypedOrderAddressCollection`
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
    - changed signature of `__construct` method. New dependency on `Symfony\Contracts\Translation\TranslatorInterface` was added.
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
* class `FlatRateMethodIdentifierGenerator` is removed in favor of `PrefixedIntegrationIdentifierGenerator`.
* previously deprecated `FlatRateMethodFromChannelBuilder`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/FlatRateShippingBundle/Builder/FlatRateMethodFromChannelBuilder.php "Oro\Bundle\FlatRateShippingBundle\Builder\FlatRateMethodFromChannelBuilder")</sup> is removed now. Use `FlatRateMethodFromChannelFactory`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/FlatRateShippingBundle/Factory/FlatRateMethodFromChannelFactory.php "Oro\Bundle\FlatRateShippingBundle\Factory\FlatRateMethodFromChannelFactory")</sup> instead.
#### OrderBundle
* removed protected method `AbstractOrderAddressType::getDefaultAddressKey`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/OrderBundle/Form/Type/AbstractOrderAddressType.php#L173 "Oro\Bundle\OrderBundle\Form\Type\AbstractOrderAddressType::getDefaultAddressKey")</sup>. Please, use method `TypedOrderAddressCollection::getDefaultAddressKey` instead
#### PayPalBundle
* class `Gateway`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/PayPalBundle/PayPal/Payflow/Gateway.php "Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway")</sup>
    - constants `PRODUCTION_HOST_ADDRESS`, `PILOT_HOST_ADDRESS`, `PRODUCTION_FORM_ACTION`, `PILOT_FORM_ACTION` removed.
* previously deprecated `PayPalPasswordType` is removed. Use `OroEncodedPlaceholderPasswordType` instead.
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
* removed protected method `QuoteAddressType::getDefaultAddressKey`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/SaleBundle/Form/Type/QuoteAddressType.php#L235 "Oro\Bundle\SaleBundle\Form\Type\QuoteAddressType::getDefaultAddressKey")</sup>. Please, use method `TypedOrderAddressCollection::getDefaultAddressKey` instead
#### ShippingBundle
* service `oro_shipping.shipping_method.registry` was removed, new `oro_shipping.shipping_method_provider` service is used instead
* class `ShippingMethodRegistry` was removed, logic was moved to `CompositeShippingMethodProvider`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/ShippingBundle/Method/CompositeShippingMethodProvider.php "Oro\Bundle\ShippingBundle\Method\CompositeShippingMethodProvider")</sup>
    - method `getTrackingAwareShippingMethods` moved to class `TrackingAwareShippingMethodsProvider`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.3.0/src/Oro/Bundle/ShippingBundle/Method/TrackingAwareShippingMethodsProvider.php "Oro\Bundle\ShippingBundle\Method\TrackingAwareShippingMethodsProvider")</sup>
* previously deprecated interface `IntegrationMethodIdentifierGeneratorInterface` is removed along with its implementations and usages. Use `IntegrationIdentifierGeneratorInterface` instead.
* previously deprecated `ShippingMethodsConfigsRuleRepository::getConfigsWithEnabledRuleAndMethod` method is removed now. Use `getEnabledRulesByMethod` method instead.
* previously deprecated `AbstractIntegrationRemovalListener` is removed now. Use `IntegrationRemovalListener` instead.
* `OroShippingBundle:Form:type/result.html.twig` and `OroShippingBundle:Form:type/selection.html.twig` - removed
#### UPSBundle
* class `UPSMethodIdentifierGenerator` is removed in favor of `PrefixedIntegrationIdentifierGenerator`.
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
* form type `PayPalPasswordType`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/PayPalBundle/Form/Type/PayPalPasswordType.php "Oro\Bundle\PayPalBundle\Form\Type\PayPalPasswordType")</sup> is deprecated, will be removed in v1.3. Please use `OroEncodedPlaceholderPasswordType` instead.
* interface `CardTypesDataProviderInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/PayPalBundle/Settings/DataProvider/CardTypesDataProviderInterface.php "Oro\Bundle\PayPalBundle\Settings\DataProvider\CardTypesDataProviderInterface")</sup> is deprecated, will be removed in v1.3. Use `CreditCardTypesDataProviderInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/PayPalBundle/Settings/DataProvider/CreditCardTypesDataProviderInterface.php "Oro\Bundle\PayPalBundle\Settings\DataProvider\CreditCardTypesDataProviderInterface")</sup> instead.
#### PaymentBundle
* for supporting same approaches for working with payment methods, `PaymentMethodProvidersRegistryInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/PaymentBundle/Method/Provider/Registry/PaymentMethodProvidersRegistryInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface")</sup> and its implementation were deprecated. Related deprecation is `PaymentMethodProvidersPass`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/PaymentBundle/DependencyInjection/Compiler/PaymentMethodProvidersPass.php "Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\PaymentMethodProvidersPass")</sup>. `CompositePaymentMethodProvider`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/PaymentBundle/Method/Provider/CompositePaymentMethodProvider.php "Oro\Bundle\PaymentBundle\Method\Provider\CompositePaymentMethodProvider")</sup> which implements `PaymentMethodProviderInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/PaymentBundle/Method/Provider/PaymentMethodProviderInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface")</sup> was added instead.
#### ShippingBundle
* `ShippingMethodsConfigsRuleRepository::getConfigsWithEnabledRuleAndMethod`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/ShippingBundle/Entity/Repository/ShippingMethodsConfigsRuleRepository.php#L82 "Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository::getConfigsWithEnabledRuleAndMethod")</sup> method deprecated because it completely duplicate `getEnabledRulesByMethod`
* the `IntegrationMethodIdentifierGeneratorInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.2.0/src/Oro/Bundle/ShippingBundle/Method/Identifier/IntegrationMethodIdentifierGeneratorInterface.php "Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface")</sup> was deprecated, the `IntegrationIdentifierGeneratorInterface` should be used instead.
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
* the `CategoryBreadcrumbProvider` was added as a data provider for breadcrumbs.
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
    - Class `PayPalSettings`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Entity/PayPalSettings.php "Oro\Bundle\PayPalBundle\Entity\PayPalSettings")</sup> was created instead of `Configuration`
    - Class `PayPalExpressCheckoutPaymentMethod`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/PayPalExpressCheckoutPaymentMethod.php "Oro\Bundle\PayPalBundle\Method\PayPalExpressCheckoutPaymentMethod")</sup> was added instead of removed classes `PayflowExpressCheckout`, `PayPalPaymentsProExpressCheckout`
    - Class `PayPalCreditCardPaymentMethod`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/PayPalCreditCardPaymentMethod.php "Oro\Bundle\PayPalBundle\Method\PayPalCreditCardPaymentMethod")</sup> was added instead of removed classes `PayflowGateway`, `PayPalPaymentsPro`
    - Class `PayPalExpressCheckoutPaymentMethodView`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/View/PayPalExpressCheckoutPaymentMethodView.php "Oro\Bundle\PayPalBundle\Method\View\PayPalExpressCheckoutPaymentMethodView")</sup> was added instead of removed classes `PayflowExpressCheckout`, `PayPalPaymentsProExpressCheckout`
    - Class `PayPalCreditCardPaymentMethodView`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/View/PayPalCreditCardPaymentMethodView.php "Oro\Bundle\PayPalBundle\Method\View\PayPalCreditCardPaymentMethodView")</sup> was added instead of removed classes `PayflowGateway`, `PayPalPaymentsPro`
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
* `IntegrationRemovalListener` class was created to be used instead of `AbstractIntegrationRemovalListener`
#### UPSBundle
* *Check UPS Connection* button was added on UPS integration page. Please, see [documentation](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Resources/doc/credentials-validation.md) for more information.
#### WebCatalog Component
* new [`WebCatalogAwareInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Component/WebCatalog/Entity/WebCatalogAwareInterface.php "Oro\Component\WebCatalog\Entity\WebCatalogAwareInterface") became available for entities which are aware of `WebCatalogs`.
* new [`WebCatalogUsageProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Component/WebCatalog/Provider/WebCatalogUsageProviderInterface.php "Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface") interface:
    - provides information about assigned `WebCatalogs` to given entities (passed as an argument)
    - provides information about usage of `WebCatalog` by id
#### WebCatalogBundle
* the `WebCatalogBreadcrumbDataProvider` class was created. 
    - `getItems` method returns breadcrumbs array
### Changed
#### CatalogBundle
* the [`CategoryExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Twig/CategoryExtension.php "Oro\Bundle\CatalogBundle\Twig\CategoryExtension") class changed:
    - The construction signature of was changed and the constructor was updated with the new `ContainerInterface $container` parameter.
#### CommerceMenuBundle
* the bundle <sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CommerceMenuBundle "Oro\Bundle\CommerceMenuBundle")</sup> was moved from the [`OroCommerce`](https://github.com/orocommerce/orocommerce) package into the [`OroCRM Customer Portal`](#"https://github.com/orocrm/customer-portal") package.
* the `MenuExtension` class was updated with the following change:
    - The construction signature of was changed and the constructor accepts only one `ContainerInterface $container` parameter.
#### CustomerBundle
* the bundle moved from the [`OroCommerce`](https://github.com/orocommerce/orocommerce) package into the [`OroCRM Customer Portal`](https://github.com/orocrm/customer-portal) package.
* the `FrontendOwnerTreeProvider::_construct` method was added with the following signature:

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
    - the class changed to `UPSTransport`
    - the publicity set to `false`
#### MoneyOrderBundle
* the bundle <sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle "Oro\Bundle\MoneyOrderBundle")</sup> implementation was changed using `IntegrationBundle` (refer to `PaymentBundle` and `IntegrationBundle` for details).
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
* the bundle <sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/WebsiteBundle "Oro\Bundle\WebsiteBundle")</sup> moved from the [`OroCommerce`](https://github.com/orocommerce/orocommerce) package into the [`OroCRM Customer Portal`](https://github.com/orocrm/customer-portal) package.
* the [`WebsiteBundle`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/WebsiteBundle "Oro\Bundle\WebsiteBundle") moved from the [`OroCommerce`](https://github.com/orocommerce/orocommerce) package into the [`OroCRM Customer Portal`](https://github.com/orocrm/customer-portal) package.
* the `OroWebsiteExtension` class changed:
    - The construction signature of was changed and the constructor was updated to have only one parameter: `ContainerInterface $container`.
* the `WebsitePathExtension` class changed:
    - The construction signature of was changed and the constructor was updated to have only one parameter: `ContainerInterface $container`.
#### WebsiteSearchBundle
* the `Driver::writeItem` and `Driver::flushWrites` should be used instead of `Driver::saveItems`
### Deprecated
#### CatalogBundle
* the [`CategoryProvider::getBreadcrumbs`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Layout/DataProvider/CategoryProvider.php "Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider") method  is deprecated. Please use
    CategoryBreadcrumbProvider::getItems()` instead.
#### InventoryBundle
* in the`/api/inventorylevels` REST API resource, the `productUnitPrecision.unit.code` filter was marked as deprecated. The `productUnitPrecision.unit.id` filter should be used instead.
#### ShippingBundle
* `AbstractIntegrationRemovalListener` was deprecated, `IntegrationRemovalListener` was created instead.
### Removed
#### CatalogBundle
* the [`CategoryExtension::setContainer`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CatalogBundle/Twig/CategoryExtension.php "Oro\Bundle\CatalogBundle\Twig\CategoryExtension") method was removed.
* the [`CategoryExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Twig/CategoryExtension.php "Oro\Bundle\CatalogBundle\Twig\CategoryExtension") class changed:
    - The `setContainer` method was removed.
* the [`CategoryPageVariantType`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Form/Type/CategoryPageVariantType.php "Oro\Bundle\CatalogBundle\Form\Type\CategoryPageVariantType") was removed and the logic moved to [`PageVariantTypeExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Form/Extension/PageVariantTypeExtension.php "Oro\Bundle\WebCatalogBundle\Form\Extension\PageVariantTypeExtension")
#### CustomerBundle
* the property `protected $securityProvider` was removed from the [`CustomerExtension`](https://github.com/oroinc/orocommerce/blob/1.0.0/src/Oro/Bundle/CustomerBundle/Twig/CustomerExtension.php "Oro\Bundle\CustomerBundle\Twig\CustomerExtension") class.
* the [`FrontendCustomerUserRoleOptionsProvider`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CustomerBundle/Layout/DataProvider/FrontendCustomerUserRoleOptionsProvider.php "Oro\Bundle\CustomerBundle\Layout\DataProvider\FrontendCustomerUserRoleOptionsProvider") class was removed and replaced with:
    - FrontendCustomerUserRoleCapabilitySetOptionsProvider` for getting capability set options
    - `FrontendCustomerUserRoleTabOptionsProvider` for getting tab options
#### MoneyOrderBundle
* the [`Configuration`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/MoneyOrderBundle/DependencyInjection/Configuration.php "Oro\Bundle\MoneyOrderBundle\DependencyInjection\Configuration") class was removed. Use [`MoneyOrderSettings`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Entity/MoneyOrderSettings.php "Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings") entity that extends the [`Transport`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/IntegrationBundle/Entity/Transport.php "Oro\Bundle\IntegrationBundle\Entity\Transport") class to store payment integration properties.
#### PayPalBundle
* implementation was changed using IntegrationBundle (refer to PaymentBundle and IntegrationBundle for details):
    - Class `Configuration` was removed and instead `PayPalSettings`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Entity/PayPalSettings.php "Oro\Bundle\PayPalBundle\Entity\PayPalSettings")</sup> was created - entity that implements `Transport`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/IntegrationBundle/Entity/Transport.php "Oro\Bundle\IntegrationBundle\Entity\Transport")</sup> to store paypal payment integration properties
    - Classes `PayflowExpressCheckoutConfig`, `PayPalPaymentsProExpressCheckoutConfig` were removed and instead simple parameter bag object `PayPalExpressCheckoutConfig`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/Config/PayPalExpressCheckoutConfig.php "Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfig")</sup> is being used for holding payment integration properties that are stored in PayPalSettings
    - Classes `PayflowGatewayConfig`, `PayPalPaymentsProConfig` were removed and instead simple parameter bag object `PayPalCreditCardConfig`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/Config/PayPalCreditCardConfig.php "Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig")</sup> is being used for holding payment integration properties that are stored in PayPalSettings
    - Classes `PayflowExpressCheckout`, `PayPalPaymentsProExpressCheckout` were removed and instead was added `PayPalExpressCheckoutPaymentMethod`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/PayPalExpressCheckoutPaymentMethod.php "Oro\Bundle\PayPalBundle\Method\PayPalExpressCheckoutPaymentMethod")</sup>
    - Classes `PayflowGateway`, `PayPalPaymentsPro` were removed and instead was added `PayPalCreditCardPaymentMethod`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/PayPalCreditCardPaymentMethod.php "Oro\Bundle\PayPalBundle\Method\PayPalCreditCardPaymentMethod")</sup>
    - Classes `PayflowExpressCheckout`, `PayPalPaymentsProExpressCheckout` were removed and instead was added `PayPalExpressCheckoutPaymentMethodView`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/View/PayPalExpressCheckoutPaymentMethodView.php "Oro\Bundle\PayPalBundle\Method\View\PayPalExpressCheckoutPaymentMethodView")</sup>
    - Classes `PayflowGateway`, `PayPalPaymentsPro` were removed and instead was added `PayPalCreditCardPaymentMethodView`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle/Method/View/PayPalCreditCardPaymentMethodView.php "Oro\Bundle\PayPalBundle\Method\View\PayPalCreditCardPaymentMethodView")</sup>
#### PaymentBundle
* in order to have possibility to create more than one payment method of same type PaymentBundle was significantly changed **with breaking backwards compatibility**.
    - Class `PaymentMethodRegistry` was removed, logic was moved to `PaymentMethodProvidersRegistry`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/Registry/PaymentMethodProvidersRegistry.php "Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistry")</sup> which implements `PaymentMethodProvidersRegistryInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/Registry/PaymentMethodProvidersRegistryInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface")</sup> and this registry is responsible for collecting data from all payment method providers
    - Class `PaymentMethodViewRegistry` was removed, logic was moved to `CompositePaymentMethodViewProvider`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/CompositePaymentMethodViewProvider.php "Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider")</sup> which implements `PaymentMethodViewProviderInterface`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewProviderInterface.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface")</sup> this composite provider is single point to provide data from all payment method view providers
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
* Class `Configuration` is removed, `PaymentTermSettings`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentTermBundle/Entity/PaymentTermSettings.php "Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings")</sup> was created instead
* PaymentTerm implementation was changed using IntegrationBundle (refer to PaymentBundle and IntegrationBundle for details).
    - Class `Configuration` was removed and instead `PaymentTermSettings`<sup>[[?]](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentTermBundle/Entity/PaymentTermSettings.php "Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings")</sup> was created - entity that implements `Transport` to store payment integration properties
    - Class `PaymentTermConfig` was removed and instead simple parameter bag object `ParameterBagPaymentTermConfig` is used for holding payment integration properties that are stored in PaymentTermSettings
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
    - field `priority` was removed. Field `_position` from `SortableExtension` is used instead.
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
* the class [`InvalidateCacheScheduleCommand`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Command/InvalidateCacheScheduleCommand.php "Oro\Bundle\UPSBundle\Command\InvalidateCacheScheduleCommand") was removed, `InvalidateCacheScheduleCommand` should be used instead
* the class [`InvalidateCacheAtHandler`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Handler/InvalidateCacheAtHandler.php "Oro\Bundle\UPSBundle\Handler\InvalidateCacheAtHandler") was removed,`InvalidateCacheActionHandler` should be used instead
* resource [`invalidateCache.html.twig`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Resources/views/Action/invalidateCache.html.twig "Oro\Bundle\UPSBundle\Resources\views\Action\invalidateCache.html.twig") was removed, use corresponding resource from CacheBundle
* resource [`invalidate-cache-button-component.js`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Resources/public/js/app/components/invalidate-cache-button-component.js "Oro\Bundle\UPSBundle\Resources\public\js\app\components\invalidate-cache-button-component.js") was removed , use corresponding resource from CacheBundle
#### WebsiteBundle
* the `protected $websiteManager` property was removed from `OroWebsiteExtension`
* the `protected $websiteUrlResolver` property was removed from `WebsitePathExtension`
#### WebsiteSearchBundle
* the following method [`IndexationRequestListener::getEntitiesWithUpdatedIndexedFields`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/WebsiteSearchBundle/EventListener/IndexationRequestListener.php "Oro\Bundle\WebsiteSearchBundle\EventListener\IndexationRequestListener") was removed 
