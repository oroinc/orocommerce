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

### Changed
OroPlatform and OroCRM have been upgraded to 2.3.4 version

### Fixed
Fixed 500 error when apply filter 'Brand' on Product creation page.

## 1.3.3 (2017-08-30)

### Changed
OroPlatform and OroCRM have been upgraded to 2.3.3 version

## 1.3.2 (2017-08-22)

### Changed
OroPlatform and OroCRM have been upgraded to 2.3.2 version

### Fixed
Fixed Filter criteria disappears from UI upon setting

## 1.3.1 (2017-08-15)

### Changed
* OroPlatform and OroCRM have been upgraded to 2.3.1 version

### Fixed
* Fixed unable to save product after Product Prices manipulations
* Fixed the product name is cached and displayed instead of the other product names in popup
* Fixed DE translations are not available via web install of application
* Fixed check out and cancel with Apruve integration periodically fails
* Fixed Sales Representative Info demo data changes

## 1.3.0 LTS (2017-07-28)
[Show detailed list of changes](#file-incompatibilities-1-3-0.md)

The class `Oro\Bundle\SecurityBundle\SecurityFacade`, services `oro_security.security_facade` and `oro_security.security_facade.link`, and TWIG function `resource_granted` were marked as deprecated.
Use services `security.authorization_checker`, `security.token_storage`, `oro_security.token_accessor`, `oro_security.class_authorization_checker`, `oro_security.request_authorization_checker` and TWIG function `is_granted` instead.
In controllers use `isGranted` method from `Symfony\Bundle\FrameworkBundle\Controller\Controller`.
The usage of deprecated service `security.context` (interface `Symfony\Component\Security\Core\SecurityContextInterface`) was removed as well.
All existing classes were updated to use new services instead of the `SecurityFacade` and `SecurityContext`:

* service `security.authorization_checker`
    * implements `Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface`
    * the property name in classes that use this service is `authorizationChecker`
* service `security.token_storage`
    * implements `Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface`
    * the property name in classes that use this service is `tokenStorage`
* service `oro_security.token_accessor`
    * implements `Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface`
    * the property name in classes that use this service is `tokenAccessor`
* service `oro_security.class_authorization_checker`
    * implements `Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker`
    * the property name in classes that use this service is `classAuthorizationChecker`
* service `oro_security.request_authorization_checker`
    * implements `Oro\Bundle\SecurityBundle\Authorization\RequestAuthorizationChecker`
    * the property name in classes that use this service is `requestAuthorizationChecker`

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

### Added
 * **CacheBundle:** added resource [`invalidateCache.html.twig`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Resources/views/Action/invalidateCache.html.twig "Oro\Bundle\UPSBundle\Resources\views\Action\invalidateCache.html.twig") from UPSBundle
 * **CacheBundle:** added resource [`invalidate-cache-button-component.js`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Resources/public/js/app/components/invalidate-cache-button-component.js "Oro\Bundle\UPSBundle\Resources\public\js\app\components\invalidate-cache-button-component.js") from UPSBundle
* **CatalogBundle:** the [`CategoryBreadcrumbProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Layout/DataProvider/CategoryBreadcrumbProvider.php "Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryBreadcrumbProvider") was added as a data provider for breadcrumbs.
* **CustomerBundle:** `commerce` configurable permission was added for View and Edit pages of the Customer Role in backend area (aka management console) (see [configurable-permissions.md](../platform/src/Oro/Bundle/SecurityBundle/Resources/doc/configurable-permissions.md) for details.
* **CustomerBundle:** `commerce_frontend` configurable permission was added for View and Edit pages of the Customer Role in frontend area (aka front store)(see [configurable-permissions.md](../platform/src/Oro/Bundle/SecurityBundle/Resources/doc/configurable-permissions.md) for details.
* **MoneyOrderBundle:** the [`MoneyOrderView`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Method/View/MoneyOrderView.php "Oro\Bundle\MoneyOrderBundle\Method\View\MoneyOrderView") class have got the following additional methods due to implementing [`Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface`](#"https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewInterface.php"):
  - The `getAdminLabel` that is used to display labels in the management console
  - The `getPaymentMethodIdentifier` that is used to properly display different payment methods on the front store
* **MoneyOrderBundle:** based on the changes in `PaymentBundle`, the following classes were added:
  * [`MoneyOrderMethodProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Method/Provider/MoneyOrderMethodProvider.php "Oro\Bundle\MoneyOrderBundle\Method\Provider\MoneyOrderMethodProvider") that provides Money Order payment methods.
  * [`MoneyOrderMethodViewProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Method/View/Provider/MoneyOrderMethodViewProvider.php "Oro\Bundle\MoneyOrderBundle\Method\View\Provider\MoneyOrderMethodViewProvider") that provides Money Order payment method views.
* **MoneyOrderBundle:** multiple classes were added to implement payment through integration and most of them have interfaces, so they are extendable through composition:
  - [`MoneyOrderSettingsType`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Form/Type/MoneyOrderSettingsType.php "Oro\Bundle\MoneyOrderBundle\Form\Type\MoneyOrderSettingsType")
  - [`MoneyOrderChannelType`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro\Bundle/MoneyOrderBundle/Integration/MoneyOrderChannelType.php "Oro\Bundle\MoneyOrderBundle\Integration\MoneyOrderChannelType")
  - [`MoneyOrderTransport`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Integration/MoneyOrderTransport.php "Oro\Bundle\MoneyOrderBundle\Integration\MoneyOrderTransport")
  - [`MoneyOrderConfigFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Method/Config/Factory/MoneyOrderConfigFactory.php "Oro\Bundle\MoneyOrderBundle\Method\Config\Factory\MoneyOrderConfigFactory")
  - [`MoneyOrderConfigProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Method/Config/Provider/MoneyOrderConfigProvider.php "Oro\Bundle\MoneyOrderBundle\Method\Config\Provider\MoneyOrderConfigProvider")
  - [`MoneyOrderPaymentMethodFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Method/Factory/MoneyOrderPaymentMethodFactory.php "Oro\Bundle\MoneyOrderBundle\Method\Factory\MoneyOrderPaymentMethodFactory")
  - [`MoneyOrderPaymentMethodViewFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Method/View/Factory/MoneyOrderPaymentMethodViewFactory.php "Oro\Bundle\MoneyOrderBundle\Method\View\Factory\MoneyOrderPaymentMethodViewFactory")
* **OrderBundle:** payment history section with payment transactions for current order was added to the order view page.
The `VIEW_PAYMENT_HISTORY` permission was added for viewing payment history section.
* **PaymentBundle:** the *organization* ownership type was added for the [`PaymentMethodsConfigsRule`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Entity/PaymentMethodsConfigsRule.php "Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule") entity.
* **PaymentBundle:** in order to have possibility to create more than one payment method of the same type, the PaymentBundle was significantly changed **with backward compatibility break**:
  - A new [`PaymentMethodProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/PaymentMethodProviderInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface") interface was added. This interface should be implemented in any payment method provider class that is responsible for providing of any payment method.
  - A new [`PaymentMethodViewProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewProviderInterface.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface") interface was added. This interface should be implemented in any payment method view provider class that is responsible for providing of any payment method view.
  - Any payment method provider should be registered in the service definitions with tag *oro_payment.payment_method_provider*.
  - Any payment method view provider should be registered in the service definitions with tag *oro_payment.payment_method_view_provider*.
  - Each payment method provider should provide one or more payment methods which should implement [`PaymentMethodInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/PaymentMethodInterface.php "Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface").
  - Each payment method view provider should provide one or more payment method views which should implement [`PaymentMethodViewInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewInterface.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface").
  - To aggregate the shared logic of all payment method providers, the [`AbstractPaymentMethodProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/AbstractPaymentMethodProvider.php "Oro\Bundle\PaymentBundle\Method\Provider\AbstractPaymentMethodProvider") was created. Any new payment method provider should extend this class.
  - To aggregate the shared logic of all payment method view providers, the [`AbstractPaymentMethodViewProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/AbstractPaymentMethodViewProvider.php "Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider") was created. Any new payment method view provider should extend this class.
* **PaymentTermBundle:** added multiple classes to implement payment through integration and most of them have interfaces, so they are extendable through composition:
    - `Oro\Bundle\PaymentTermBundle\Entity\Repository\PaymentTermSettingsRepository`
    - `Oro\Bundle\PaymentTermBundle\Form\Type\PaymentTermSettingsType`
    - `Oro\Bundle\PaymentTermBundle\Integration\PaymentTermChannelType`
    - `Oro\Bundle\PaymentTermBundle\Integration\PaymentTermTransport`
    - `Oro\Bundle\PaymentTermBundle\Method\Config\ParameterBag\ParameterBagPaymentTermConfig`
    - `Oro\Bundle\PaymentTermBundle\Method\Config\Provider\Basic\BasicPaymentTermConfigProvider`
    - `Oro\Bundle\PaymentTermBundle\Method\Config\Provider\Cached\Memory\CachedMemoryPaymentTermConfigProvider`
    - `Oro\Bundle\PaymentTermBundle\Method\Factory\PaymentTermPaymentMethodFactory`
    - `Oro\Bundle\PaymentTermBundle\Method\Provider\PaymentTermMethodProvider`
    - `Oro\Bundle\PaymentTermBundle\Method\View\Factory\PaymentTermPaymentMethodViewFactory`
    - `Oro\Bundle\PaymentTermBundle\Method\View\Provider\PaymentTermMethodViewProvider`
* **PaymentTermBundle:** class `Oro\Bundle\PaymentTermBundle\Method\View\PaymentTermView` now has two additional methods due to implementing `Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface`
    - getAdminLabel() is used to display labels in admin panel
    - getPaymentMethodIdentifier() used to properly display different methods in frontend
* **PaymentTermBundle:** Class `Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings` was created instead of `Oro\Bundle\PaymentTermBundle\DependencyInjection\Configuration`

 * **PayPalBundle:** implementation was changed using IntegrationBundle (refer to PaymentBundle and IntegrationBundle for details):
    - Class `Oro\Bundle\PayPalBundle\Entity\PayPalSettings` was created instead of `Oro\Bundle\PayPalBundle\DependencyInjection\Configuration`
    - Class `Oro\Bundle\PayPalBundle\Method\PayPalExpressCheckoutPaymentMethod` was added instead of removed classes `Oro\Bundle\PayPalBundle\Method\PayflowExpressCheckout`, `Oro\Bundle\PayPalBundle\Method\PayPalPaymentsProExpressCheckout`
    - Class `Oro\Bundle\PayPalBundle\Method\PayPalCreditCardPaymentMethod` was added instead of removed classes `Oro\Bundle\PayPalBundle\Method\PayflowGateway`, `Oro\Bundle\PayPalBundle\Method\PayPalPaymentsPro` 
    - Class `Oro\Bundle\PayPalBundle\Method\View\PayPalExpressCheckoutPaymentMethodView` was added instead of removed classes `Oro\Bundle\PayPalBundle\Method\View\PayflowExpressCheckout`, `Oro\Bundle\PayPalBundle\Method\View\PayPalPaymentsProExpressCheckout`
    - Class `Oro\Bundle\PayPalBundle\Method\View\PayPalCreditCardPaymentMethodView` was added instead of removed classes `Oro\Bundle\PayPalBundle\Method\View\PayflowGateway`, `Oro\Bundle\PayPalBundle\Method\View\PayPalPaymentsPro`

* **PayPalBundle:** according to changes in PaymentBundle were added:
    - `Oro\Bundle\PayPalBundle\Method\Provider\CreditCardMethodProvider` for providing *PayPal Credit Card Payment Methods*
    - `Oro\Bundle\PayPalBundle\Method\View\Provider\CreditCardMethodViewProvider` for providing *PayPal Credit Card Payment Method Views*
    - `Oro\Bundle\PayPalBundle\Method\Provider\ExpressCheckoutMethodProvider` for providing *PayPal Express Checkout Payment Methods*
    - `Oro\Bundle\PayPalBundle\Method\View\Provider\ExpressCheckoutMethodViewProvider` for providing *PayPal Express Checkout Payment Method Views*
* **PayPalBundle:** added multiple classes to implement payment through integration and most of them have interfaces, so they are extendable through composition:
    - `Oro\Bundle\PayPalBundle\Form\Type\PayPalSettingsType`
    - `Oro\Bundle\PayPalBundle\Integration\PayPalPayflowGatewayChannelType`
    - `Oro\Bundle\PayPalBundle\Integration\PayPalPayflowGatewayTransport`
    - `Oro\Bundle\PayPalBundle\Integration\PayPalPaymentsProChannelType`
    - `Oro\Bundle\PayPalBundle\Integration\PayPalPaymentsProTransport`
    - `Oro\Bundle\PayPalBundle\Method\Config\AbstractPayPalConfig`
    - `Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig`
    - `Oro\Bundle\PayPalBundle\Method\Config\Factory\AbstractPayPalConfigFactory`
    - `Oro\Bundle\PayPalBundle\Method\Config\Factory\PayPalCreditCardConfigFactory`
    - `Oro\Bundle\PayPalBundle\Method\Config\Factory\PayPalExpressCheckoutConfigFactory`
    - `Oro\Bundle\PayPalBundle\Method\Config\Provider\AbstractPayPalConfigProvider`
    - `Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalCreditCardConfigProvider`
    - `Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalExpressCheckoutConfigProvider`
    - `Oro\Bundle\PayPalBundle\Method\Factory\BasicPayPalCreditCardPaymentMethodFactory`
    - `Oro\Bundle\PayPalBundle\Method\Factory\BasicPayPalExpressCheckoutPaymentMethodFactory`
    - `Oro\Bundle\PayPalBundle\Method\View\Factory\BasicPayPalCreditCardPaymentMethodViewFactory`
    - `Oro\Bundle\PayPalBundle\Method\View\Factory\BasicPayPalExpressCheckoutPaymentMethodViewFactory`
    - `Oro\Bundle\PayPalBundle\Settings\DataProvider\BasicCardTypesDataProvider`
    - `Oro\Bundle\PayPalBundle\Settings\DataProvider\BasicPaymentActionsDataProvider`
* **ProductBundle:** added classes that can decorate `Oro\Bundle\ProductBundle\Entity\Product` to have virtual fields:
    - `Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory` is the class that should be used to create a decorated `Product`
    - `Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecorator` is the class that decorates `Product`
    - `Oro\Bundle\ProductBundle\VirtualFields\QueryDesigner\VirtualFieldsSelectQueryConverter` this converter is used inside of `VirtualFieldsProductDecorator`
    - `Oro\Bundle\ProductBundle\VirtualFields\QueryDesigner\VirtualFieldsProductQueryDesigner` this query designer is used inside of `VirtualFieldsProductDecorator`
* **RuleBundle:** added `Oro\Bundle\RuleBundle\Entity\RuleInterface` this interface should now be used for injection instead of `Rule` in bundles that implement `RuleBundle` functionality
* **RuleBundle:** added classes for handling enable/disable `Rule` actions - use them to define corresponding services
    - `Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\StatusMassActionHandler`
    - `Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction`
    - `Oro\Bundle\RuleBundle\Datagrid\RuleActionsVisibilityProvider`
* **RuleBundle:** added `RuleActionsVisibilityProvider` that should be used to define action visibility configuration in datagrids with `Rule` entity fields
* **ShippingBundle:** [`IntegrationRemovalListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Method/EventListener/IntegrationRemovalListener.php "Oro\Bundle\ShippingBundle\Method\EventListener\IntegrationRemovalListener") class was created to be used instead of [`AbstractIntegrationRemovalListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Method/EventListener/AbstractIntegrationRemovalListener.php "Oro\Bundle\ShippingBundle\Method\EventListener\AbstractIntegrationRemovalListener")
* **UPSBundle:** *Check UPS Connection* button was added on UPS integration page. Please, see [documentation](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Resources/doc/credentials-validation.md) for more information.
* **WebCatalog Component:** new [`WebCatalogAwareInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Component/WebCatalog/Entity/WebCatalogAwareInterface.php "Oro\Component\WebCatalog\Entity\WebCatalogAwareInterface") became available for entities which are aware of `WebCatalogs`.
* **WebCatalog Component:** new [`WebCatalogUsageProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Component/WebCatalog/Provider/WebCatalogUsageProviderInterface.php "Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface") interface:
    - provides information about assigned `WebCatalogs` to given entities (passed as an argument)
    - provides information about usage of `WebCatalog` by id
* **WebCatalogBundle:** the [`AbstractWebCatalogDataProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Layout/DataProvider/AbstractWebCatalogDataProvider.php "Oro\Bundle\WebCatalogBundle\Layout\DataProvider\AbstractWebCatalogDataProvider") class was created to unify Providers for MenuData and WebCatalogBreadcrumb
* **WebCatalogBundle:** the [`WebCatalogBreadcrumbDataProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Layout/DataProvider/WebCatalogBreadcrumbDataProvider.php "Oro\Bundle\WebCatalogBundle\Layout\DataProvider\WebCatalogBreadcrumbDataProvider") class was created. 
    - `getItems` method returns breadcrumbs array
### Changed
* **CatalogBundle:** the [`CategoryExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Twig/CategoryExtension.php "Oro\Bundle\CatalogBundle\Twig\CategoryExtension") class changed:
    - The construction signature of was changed and the constructor was updated with the new `ContainerInterface $container` parameter.
* **CMSBundle:** the following methods were moved from the [`CmsPageVariantType`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CMSBundle/Form/Type/CmsPageVariantType.php "Oro\Bundle\CMSBundle\Form\Type\CmsPageVariantType") class to the [`PageVariantTypeExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Form/Extension/PageVariantTypeExtension.php "Oro\Bundle\WebCatalogBundle\Form\Extension\PageVariantTypeExtension") class:
   - `__construct`
   - `configureOptions`
* [**CommerceMenuBundle:**](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CommerceMenuBundle "Oro\Bundle\CommerceMenuBundle") moved from the [`OroCommerce`](https://github.com/orocommerce/orocommerce) package into the [`OroCRM Customer Portal`](#"https://github.com/orocrm/customer-portal") package.
* **CommerceMenuBundle:** the [`MenuExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CommerceMenuBundle/Twig/MenuExtension.php "Oro\Bundle\CommerceMenuBundle\Twig\MenuExtension") class was updated with the following change:
    - The construction signature of was changed and the constructor accepts only one `ContainerInterface $container` parameter.
* [**CustomerBundle:**](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CustomerBundle "Oro\Bundle\CustomerBundle") moved from the [`OroCommerce`](https://github.com/orocommerce/orocommerce) package into the [`OroCRM Customer Portal`](https://github.com/orocrm/customer-portal) package.
* **CustomerBundle:** the [`FrontendOwnerTreeProvider::_construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CustomerBundle/OwnerFrontendOwnerTreeProvider.php "Oro\Bundle\CustomerBundle\Owner\FrontendOwnerTreeProvider") method was added with the following signature:

  ```
  __construct(
        ManagerRegistry $doctrine,
        DatabaseChecker $databaseChecker,
        CacheProvider $cache,
        MetadataProviderInterface $ownershipMetadataProvider,
        TokenStorageInterface $tokenStorage
    )
  ```
* **CustomerBundle:** the construction signature of the [`CustomerExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CustomerBundle/Twig/CustomerExtension.php "Oro\Bundle\CustomerBundle\Twig\CustomerExtension") class was changed and the constructor accepts only one `ContainerInterface $container` parameter.
* [**FlatRateBundle:**](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/FlatRateBundle/ "Oro\Bundle\FlatRateBundle") was renamed to [`FlatRateShippingBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/FlatRateShippingBundle/ "Oro\Bundle\FlatRateShippingBundle") 
* [**FrontendBundle:**](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/FrontendBundle "Oro\Bundle\FrontendBundle") moved from the [`OroCommerce`](https://github.com/orocommerce/orocommerce) package into the [`OroCRM Customer Portal`](https://github.com/orocrm/customer-portal) package.
* **FrontendLocalizationBundle:** the service definition for `oro_frontend_localization.extension.transtation_packages_provider` was updated in a following way: 
    - the class changed to [`UPSTransport::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/FrontendBundle/Provider/TranslationPackagesProviderExtension.php "Oro\Bundle\FrontendBundle\Provider\TranslationPackagesProviderExtension")
    - the publicity set to `false`
* **FrontendTestFrameworkBundle:** the [`FrontendWebTestCase::tearDown`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/FrontendTestFrameworkBundle/Test/FrontendWebTestCase.php "Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase") method was renamed to [`FrontendWebTestCase::afterFrontendTest`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/FrontendTestFrameworkBundle/Test/FrontendWebTestCase.php "Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase").
* **InventoryBundle:** the [`InventoryLevelReader::setSourceEntityName`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/InventoryBundle/ImportExport/Reader/InventoryLevelReader.php "Oro\Bundle\InventoryBundle\ImportExport\Reader\InventoryLevelReader") was updated so you could pass an array of IDs in the third argument.
* [**MoneyOrderBundle:**](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle "Oro\Bundle\MoneyOrderBundle") implementation was changed using `IntegrationBundle` (refer to `PaymentBundle` and `IntegrationBundle` for details).
* **MoneyOrderBundle:** the [`MoneyOrderConfig`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Method/Config/MoneyOrderConfig.php "Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig") class was implemented as `ParameterBag` that keeps the payment integration properties that are stored in [`MoneyOrderSettings`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Entity/MoneyOrderSettings.php "Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings").
* [**PayPalBundle:**](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PayPalBundle "Oro\Bundle\PayPalBundle") implementation was changed using IntegrationBundle (refer to PaymentBundle and IntegrationBundle for details).
* **PaymentBundle:** in the [`DecoratedProductLineItemFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/ExpressionLanguage/DecoratedProductLineItemFactory.php "Oro\Bundle\PaymentBundle\ExpressionLanguage\DecoratedProductLineItemFactory") class, the only dependency is now 
[`VirtualFieldsProductDecoratorFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/VirtualFields/VirtualFieldsProductDecoratorFactory.php "Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory").
* **PaymentBundle:** in order to have possibility to create more than one payment method of same type PaymentBundle was significantly changed **with breaking backwards compatibility**.
    - Class `Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry` was changed to `Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistry` which implements `Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface` and this registry is responsible for collecting data from all payment method providers
    - Class `Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry` was changed to `Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider` which implements `Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface` this composite provider is single point to provide data from all payment method view providers

* [**PaymentTermBundle:**](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentTermBundle "Oro\Bundle\PaymentTermBundle") implementation was changed using IntegrationBundle (refer to PaymentBundle and IntegrationBundle for details).
* **PaymentTermBundle:** PaymentTerm implementation was changed using IntegrationBundle (refer to PaymentBundle and IntegrationBundle for details).
    * Class `Oro\Bundle\PaymentTermBundle\Method\PaymentTerm` method getIdentifier now uses PaymentTermConfig to retrieve identifier of a concrete method
* **PricingBundle:** class `Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository` changes:
    - changed the return type of `getCombinedPriceListsByPriceList` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    - changed the return type of `getCombinedPriceListsByPriceLists` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    - changed the return type of `getCPLsForPriceCollectByTimeOffset` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
* **PricingBundle:** class `Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerFallbackRepository` changes:
    - changed the return type of `getCustomerIdentityByGroup` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
* **PricingBundle:** class `Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerGroupFallbackRepository` changes:
    - changed the return type of `getCustomerIdentityByWebsite` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
* **PricingBundle:** class `Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository` changes:
    - changed the return type of `getPriceListsWithRules` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
* **PricingBundle:** class `Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository` changes:
    - changed the return type of `getCustomerGroupIteratorByDefaultFallback` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    - changed the return type of `getIteratorByPriceList` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
* **PricingBundle:** class `Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository` changes:
    - changed the return type of `getCustomerIteratorByDefaultFallback` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    - changed the return type of `getCustomerWebsitePairsByCustomerGroupIterator` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    - changed the return type of `getIteratorByPriceList` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
* **PricingBundle:** class `Oro\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository` changes:
    - changed the return type of `getWebsiteIteratorByDefaultFallback` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
* **PricingBundle:** class `Oro\Bundle\PricingBundle\Entity\BasePriceListRelation` changes:
    - property `$priority` was renamed to `$sortOrder`
    - methods `getPriority` and `setPriority` were renamed to `getSortOrder` and `setSortOrder` accordingly
* **PricingBundle:** class `Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig` changes:
    - property `$priority` was renamed to `$sortOrder`
    - methods `getPriority` and `setPriority` were renamed to `getSortOrder` and `setSortOrder` accordingly
* **PricingBundle:** interface `Oro\Bundle\PricingBundle\Entity\PriceListAwareInterface` changes:
    - method `getPriority` was renamed to `getSortOrder`
* **PricingBundle:** class `Oro\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter` changes:
    - constant `PRIORITY_KEY` was renamed to `SORT_ORDER_KEY`
 * **ShippingBundle:** in the [`DecoratedProductLineItemFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/ExpressionLanguage/DecoratedProductLineItemFactory.php "Oro\Bundle\ShippingBundle\ExpressionLanguage\DecoratedProductLineItemFactory") class, the only dependency is now 
[`VirtualFieldsProductDecoratorFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/VirtualFields/VirtualFieldsProductDecoratorFactory.php "Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory").
* **TaxBundle:** the following methods were updated: 
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
* **Tree Component:** the [`AbstractTreeHandler::getTreeItemList()`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Component/Tree/Handler/AbstractTreeHandler.php "Oro\Component\Tree\Handler\AbstractTreeHandler") method was added.
* **VisibilityBundle:** in [`AbstractCustomerPartialUpdateDriver`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/VisibilityBundle/Driver/AbstractCustomerPartialUpdateDriver.php "Oro\Bundle\VisibilityBundle\Driver\AbstractCustomerPartialUpdateDriver"), the return type of the `getCustomerVisibilityIterator` method changed from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`.
* [**WebsiteBundle:**](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/WebsiteBundle "Oro\Bundle\WebsiteBundle") moved from the [`OroCommerce`](https://github.com/orocommerce/orocommerce) package into the [`OroCRM Customer Portal`](https://github.com/orocrm/customer-portal) package.
* **WebsiteBundle:** the [`WebsiteBundle`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/WebsiteBundle "Oro\Bundle\WebsiteBundle") moved from the [`OroCommerce`](https://github.com/orocommerce/orocommerce) package into the [`OroCRM Customer Portal`](https://github.com/orocrm/customer-portal) package.
* **WebsiteBundle:** the [`OroWebsiteExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebsiteBundle/Twig/OroWebsiteExtension.php "Oro\Bundle\WebsiteBundle\Twig\OroWebsiteExtension") class changed:
    - The construction signature of was changed and the constructor was updated to have only one parameter: `ContainerInterface $container`.
* **WebsiteBundle:** the [`WebsitePathExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebsiteBundle/Twig/WebsitePathExtension.php "Oro\Bundle\WebsiteBundle\Twig\WebsitePathExtension") class changed:
        - The construction signature of was changed and the constructor was updated to have only one parameter: `ContainerInterface $container`.
* **WebsiteSearchBundle:** the `Driver::writeItem` and `Driver::flushWrites` should be used instead of `Driver::saveItems`
### Deprecated
* **CatalogBundle:** the [`CategoryProvider::getBreadcrumbs`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Layout/DataProvider/CategoryProvider.php "Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider") method  is deprecated. Please use
    [`CategoryBreadcrumbProvider::getItems()`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Layout/DataProvider/CategoryBreadcrumbProvider.php "Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryBreadcrumbProvider") instead.
* **InventoryBundle:** in the`/api/inventorylevels` REST API resource, the `productUnitPrecision.unit.code` filter was marked as deprecated. The `productUnitPrecision.unit.id` filter should be used instead.
* **ShippingBundle:** [`AbstractIntegrationRemovalListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Method/EventListener/AbstractIntegrationRemovalListener.php "Oro\Bundle\ShippingBundle\Method\EventListener\AbstractIntegrationRemovalListener") was deprecated, [`IntegrationRemovalListener`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Method/EventListener/IntegrationRemovalListener.php "Oro\Bundle\ShippingBundle\Method\EventListener\IntegrationRemovalListener") was created instead.
### Removed
* **CatalogBundle:** the [`CategoryExtension::setContainer`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CatalogBundle/Twig/CategoryExtension.php "Oro\Bundle\CatalogBundle\Twig\CategoryExtension") method was removed.
* **CatalogBundle:** the [`CategoryExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Twig/CategoryExtension.php "Oro\Bundle\CatalogBundle\Twig\CategoryExtension") class changed:
    - The `setContainer` method was removed.
* **CatalogBundle:** the [`CategoryPageVariantType::__construct`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CatalogBundle/Form/Type/CategoryPageVariantType.php "Oro\Bundle\CatalogBundle\Form\Type\CategoryPageVariantType") was removed and the logic moved to [`PageVariantTypeExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebCatalogBundle/Form/Extension/PageVariantTypeExtension.php "Oro\Bundle\WebCatalogBundle\Form\Extension\PageVariantTypeExtension")
* **CustomerBundle:** the property `protected $securityProvider` was removed from the [`CustomerExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CustomerBundle/Twig/CustomerExtension.php "Oro\Bundle\CustomerBundle\Twig\CustomerExtension") class.
* **CustomerBundle:** the [`FrontendCustomerUserRoleOptionsProvider`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/CustomerBundle/Layout/DataProvider/FrontendCustomerUserRoleOptionsProvider.php "Oro\Bundle\CustomerBundle\Layout\DataProvider\FrontendCustomerUserRoleOptionsProvider") class was removed and replaced with:
    - [`FrontendCustomerUserRoleCapabilitySetOptionsProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CustomerBundle/Layout/DataProvider/FrontendCustomerUserRoleCapabilitySetOptionsProvider.php "Oro\Bundle\CustomerBundle\Layout\DataProvider\FrontendCustomerUserRoleCapabilitySetOptionsProvider") for getting capability set options
    - [`FrontendCustomerUserRoleTabOptionsProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CustomerBundle/Layout/DataProvider/FrontendCustomerUserRoleTabOptionsProvider.php "Oro\Bundle\CustomerBundle\Layout\DataProvider\FrontendCustomerUserRoleTabOptionsProvider") for getting tab options
 * **MoneyOrderBundle:** the [`Configuration`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/MoneyOrderBundle/DependencyInjection/Configuration.php "Oro\Bundle\MoneyOrderBundle\DependencyInjection\Configuration") class was removed. Use [`MoneyOrderSettings`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/MoneyOrderBundle/Entity/MoneyOrderSettings.php "Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings") entity that extends the [`Transport`](https://github.com/orocrm/platform/tree/2.1.0/src/Oro/Bundle/IntegrationBundle/Entity/Transport.php "Oro\Bundle\IntegrationBundle\Entity\Transport") class to store payment integration properties.
 * **PaymentBundle:** the following classes (that are related to the actions that disable/enable
[`PaymentMethodsConfigsRule`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Entity/PaymentMethodsConfigsRule.php "Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule")) were abstracted and moved to the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle") (see the [`RuleBundle`](#RuleBundle)) section for more information):
  - [`StatusDisableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Datagrid/Extension/MassAction/Actions/StatusDisableMassAction.php "Oro\Bundle\PaymentBundle\Datagrid\Extension\MassAction\Actions\StatusDisableMassAction") (is replaced with [`StatusEnableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/Extension/MassAction/Actions/StatusEnableMassAction.php "Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle"))
  - [`StatusEnableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Datagrid/Extension/MassAction/Actions/StatusEnableMassAction.php "Oro\Bundle\PaymentBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction") (is replaced with [`StatusEnableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/Extension/MassAction/Actions/StatusEnableMassAction.php "Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle"))
  - [`StatusMassActionHandler`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Datagrid/Extension/MassAction/StatusMassActionHandler.php "Oro\Bundle\PaymentBundle\Datagrid\Extension\MassAction\StatusMassActionHandler") (is replaced with [`StatusMassActionHandler`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/Extension/MassAction/StatusMassActionHandler.php "Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\StatusMassActionHandler") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle"))
  - [`PaymentRuleActionsVisibilityProvider`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Datagrid/PaymentRuleActionsVisibilityProvider.php "Oro\Bundle\PaymentBundle\Datagrid\PaymentRuleActionsVisibilityProvider") (is replaced with [`PaymentRuleActionsVisibilityProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/PaymentRuleActionsVisibilityProvider.php "Oro\Bundle\RuleBundle\Datagrid\PaymentRuleActionsVisibilityProvider") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle"))
 * **PaymentBundle:** the following classes (that are related to decorating [`Product`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ProductBundle/Entity/Product.php "Oro\Bundle\ProductBundle\Entity\Product") with virtual fields) were abstracted and moved to the [`ProductBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle "Oro\Bundle\ProductBundle") (see the [`ProductBundle`](#ProductBundle) section for more information):
  - [`SelectQueryConverter`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/QueryDesigner/SelectQueryConverter.php "Oro\Bundle\PaymentBundle\QueryDesigner\SelectQueryConverter") 
  - [`PaymentProductQueryDesigner`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/QueryDesigner/PaymentProductQueryDesigner.php "Oro\Bundle\PaymentBundle\QueryDesigner\PaymentProductQueryDesigner") 
  - [`ProductDecorator`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/ExpressionLanguage/ProductDecorator.php "Oro\Bundle\PaymentBundle\ExpressionLanguage\ProductDecorator")
* **PaymentBundle:** in order to have possibility to create more than one payment method of the same type, the PaymentBundle was significantly changed **with backward compatibility break**:
    - The [`PaymentMethodRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/PaymentMethodRegistry.php "Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry") class was replaced with the [`PaymentMethodProvidersRegistry`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/Registry/PaymentMethodProvidersRegistry.php "Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistry") which implements a [`PaymentMethodProvidersRegistryInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/Provider/Registry/PaymentMethodProvidersRegistryInterface.php "Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface") and this registry is responsible for collecting data from all payment method providers.
    - The [`PaymentMethodViewRegistry`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewRegistry.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry") class was replaced with the [`CompositePaymentMethodViewProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/CompositePaymentMethodViewProvider.php "Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider") which implements a [`PaymentMethodViewProviderInterface`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/PaymentBundle/Method/View/PaymentMethodViewProviderInterface.php "Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface"). This composite provider is a single point to provide data from all payment method view providers.
 
 * **PaymentTermBundle:** PaymentTerm implementation was changed using IntegrationBundle (refer to PaymentBundle and IntegrationBundle for details).
    - Class `Oro\Bundle\PaymentTermBundle\DependencyInjection\Configuration` was removed and instead `Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings` was created - entity that implements `Oro\Bundle\IntegrationBundle\Entity\Transport` to store payment integration properties
    - Class `Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfig` was removed and instead simple parameter bag object `Oro\Bundle\PaymentTermBundle\Method\Config\ParameterBagPaymentTermConfig` is being used for holding payment integration properties that are stored in PaymentTermSettings
 * **PayPalBundle:** implementation was changed using IntegrationBundle (refer to PaymentBundle and IntegrationBundle for details):
    - Class `Oro\Bundle\PayPalBundle\DependencyInjection\Configuration` was removed and instead `Oro\Bundle\PayPalBundle\Entity\PayPalSettings` was created - entity that implements `Oro\Bundle\IntegrationBundle\Entity\Transport` to store paypal payment integration properties
    - Classes `Oro\Bundle\PayPalBundle\Method\Config\PayflowExpressCheckoutConfig`, `Oro\Bundle\PayPalBundle\Method\Config\PayPalPaymentsProExpressCheckoutConfig` were removed and instead simple parameter bag object `Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfig` is being used for holding payment integration properties that are stored in PayPalSettings
    - Classes `Oro\Bundle\PayPalBundle\Method\Config\PayflowGatewayConfig`, `Oro\Bundle\PayPalBundle\Method\Config\PayPalPaymentsProConfig` were removed and instead simple parameter bag object `Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig` is being used for holding payment integration properties that are stored in PayPalSettings
    - Classes `Oro\Bundle\PayPalBundle\Method\PayflowExpressCheckout`, `Oro\Bundle\PayPalBundle\Method\PayPalPaymentsProExpressCheckout` were removed and instead was added `Oro\Bundle\PayPalBundle\Method\PayPalExpressCheckoutPaymentMethod`
    - Classes `Oro\Bundle\PayPalBundle\Method\PayflowGateway`, `Oro\Bundle\PayPalBundle\Method\PayPalPaymentsPro` were removed and instead was added `Oro\Bundle\PayPalBundle\Method\PayPalCreditCardPaymentMethod`
    - Classes `Oro\Bundle\PayPalBundle\Method\View\PayflowExpressCheckout`, `Oro\Bundle\PayPalBundle\Method\View\PayPalPaymentsProExpressCheckout` were removed and instead was added `Oro\Bundle\PayPalBundle\Method\View\PayPalExpressCheckoutPaymentMethodView`
    - Classes `Oro\Bundle\PayPalBundle\Method\View\PayflowGateway`, `Oro\Bundle\PayPalBundle\Method\View\PayPalPaymentsPro` were removed and instead was added `Oro\Bundle\PayPalBundle\Method\View\PayPalCreditCardPaymentMethodView`
 * **PricingBundle:** class `Oro\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType` changes:
    - field `priority` was removed. Field `_position` from `Oro\Bundle\FormBundle\Form\Extension\SortableExtension` will be used instead.
 * **ProductBundle:** removed constructor of `Oro\Bundle\ProductBundle\Form\Type\ProductPageVariantType`.
    - corresponding logic moved to `Oro\Bundle\WebCatalogBundle\Form\Extension\PageVariantTypeExtension`
 * **RedirectBundle:** removed property `website` in favour of `scopes` collection using  from `Oro\Bundle\RedirectBundle\Entity\Redirect` class
 * **ShippingBundle:** the following classes that are related to decorating [`Product`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ProductBundle/Entity/Product.php "Oro\Bundle\ProductBundle\Entity\Product") with virtual fields) were abstracted and moved to the [`ProductBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle "Oro\Bundle\ProductBundle") (see the [`ProductBundle`](#ProductBundle) section for more information):
  - [`SelectQueryConverter`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/QueryDesigner/SelectQueryConverter.php "Oro\Bundle\ShippingBundle\QueryDesigner\SelectQueryConverter") 
  - [`ShippingProductQueryDesigner`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/QueryDesigner/ShippingProductQueryDesigner.php "Oro\Bundle\ShippingBundle\QueryDesigner\ShippingProductQueryDesigner") 
  - [`ProductDecorator`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/ExpressionLanguage/ProductDecorator.php "Oro\Bundle\ShippingBundle\ExpressionLanguage\ProductDecorator")
  - In the [`DecoratedProductLineItemFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ShippingBundle/ExpressionLanguage/DecoratedProductLineItemFactory.php "Oro\Bundle\ShippingBundle\ExpressionLanguage\DecoratedProductLineItemFactory") class, the only dependency is now 
[`VirtualFieldsProductDecoratorFactory`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/ProductBundle/VirtualFields/VirtualFieldsProductDecoratorFactory.php "Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory").
 * **ShippingBundle:** the classes that are related to actions that disable/enable [`ShippingMethodsConfigsRule`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Entity/ShippingMethodsConfigsRule.php "Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule") were abstracted and moved to the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle") (see the [`RuleBundle`](#RuleBundle)) section for more information):
  - Removed [`StatusDisableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Datagrid/Extension/MassAction/Actions/StatusDisableMassAction.php "Oro\Bundle\ShippingBundle\Datagrid\Extension\MassAction\Actions\StatusDisableMassAction") and switched definition to [`StatusEnableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/Extension/MassAction/Actions/StatusEnableMassAction.php "Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle")
  - [`StatusEnableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Datagrid/Extension/MassAction/Actions/StatusEnableMassAction.php "Oro\Bundle\ShippingBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction") and switched definition to [`StatusEnableMassAction`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/Extension/MassAction/Actions/StatusEnableMassAction.php "Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle")
  - [`StatusMassActionHandler`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Datagrid/Extension/MassAction/StatusMassActionHandler.php "Oro\Bundle\ShippingBundle\Datagrid\Extension\MassAction\StatusMassActionHandler") and switched definition to [`StatusMassActionHandler`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/Extension/MassAction/StatusMassActionHandler.php "Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\StatusMassActionHandler") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle")
  - [`ShippingRuleActionsVisibilityProvider`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/ShippingBundle/Datagrid/ShippingRuleActionsVisibilityProvider.php "Oro\Bundle\ShippingBundle\Datagrid\ShippingRuleActionsVisibilityProvider") and switched definition to [`RuleActionsVisibilityProvider`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle/Datagrid/RuleActionsVisibilityProvider.php "Oro\Bundle\RuleBundle\Datagrid\RuleActionsVisibilityProvider") in the [`RuleBundle`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/RuleBundle "Oro\Bundle\RuleBundle")
 * **UPSBundle:** the class [`InvalidateCacheScheduleCommand`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Command/InvalidateCacheScheduleCommand.php "Oro\Bundle\UPSBundle\Command\InvalidateCacheScheduleCommand") was removed, [`InvalidateCacheScheduleCommand`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/CacheBundle/Command/InvalidateCacheScheduleCommand.php "Oro\Bundle\CacheBundle\Command\InvalidateCacheScheduleCommand") should be used instead
 * **UPSBundle:** the class [`InvalidateCacheAtHandler`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Handler/InvalidateCacheAtHandler.php "Oro\Bundle\UPSBundle\Handler\InvalidateCacheAtHandler") was removed, [`InvalidateCacheActionHandler`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/UPSBundle/Handler/InvalidateCacheActionHandler.php "Oro\Bundle\UPSBundle\Handler\InvalidateCacheActionHandler") should be used instead
 * **UPSBundle:** resource [`invalidateCache.html.twig`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Resources/views/Action/invalidateCache.html.twig "Oro\Bundle\UPSBundle\Resources\views\Action\invalidateCache.html.twig") was moved to CacheBundle
 * **UPSBundle:** resource [`invalidate-cache-button-component.js`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/UPSBundle/Resources/public/js/app/components/invalidate-cache-button-component.js "Oro\Bundle\UPSBundle\Resources\public\js\app\components\invalidate-cache-button-component.js") was moved to CacheBundle
 * **WebsiteBundle:** the `protected $websiteManager` property was removed from [`OroWebsiteExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebsiteBundle/Twig/OroWebsiteExtension.php "Oro\Bundle\WebsiteBundle\Twig\OroWebsiteExtension")
 * **WebsiteBundle:** the `protected $websiteUrlResolver` property was removed from [`WebsitePathExtension`](https://github.com/orocommerce/orocommerce/tree/1.1.0/src/Oro/Bundle/WebsiteBundle/Twig/WebsitePathExtension.php "Oro\Bundle\WebsiteBundle\Twig\WebsitePathExtension")
 * **WebsiteSearchBundle:** the following method [`IndexationRequestListener::getEntitiesWithUpdatedIndexedFields`](https://github.com/orocommerce/orocommerce/tree/1.0.0/src/Oro/Bundle/WebsiteSearchBundle/EventListener/IndexationRequestListener.php "Oro\Bundle\WebsiteSearchBundle\EventListener\IndexationRequestListener") was removed 
### Fixed

## 1.0.14 (2017-08-22)

## 1.0.13 (2017-08-10)

## 1.0.2 (2017-03-21)

## 1.0.1 (2017-02-21)

## 1.0.0 (2017-01-18)