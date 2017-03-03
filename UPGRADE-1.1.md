UPGRADE FROM 1.0.0 to 1.1
=======================================

Tree Component
--------------
- `Oro\Component\Tree\Handler\AbstractTreeHandler`:
    - added method `getTreeItemList`
    
WebCatalog Component
-------------
- New Interface `Oro\Component\WebCatalog\Entity\WebCatalogAwareInterface`
    - for entities which are aware of WebCatalogs
- New Interface `Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface`
    - provide information about assigned WebCatalogs to given entities (passed as an argument)
    - provide information about usage of WebCatalog by id

CatalogBundle
-------------
- Class `Oro\Bundle\CatalogBundle\Twig\CategoryExtension`
    - the construction signature of was changed. Now the constructor has `ContainerInterface $container` parameter
    - removed method `setContainer`
- Removed constructor of `Oro\Bundle\CatalogBundle\Form\Type\CategoryPageVariantType`. 
    - corresponding logic moved to `Oro\Bundle\WebCatalogBundle\Form\Extension\PageVariantTypeExtension`


CustomerBundle
--------------
- Added the constructor to `Oro\Bundle\CustomerBundle\Owner\FrontendOwnerTreeProvider`. The constructor signature is
  ```
  __construct(
        ManagerRegistry $doctrine,
        DatabaseChecker $databaseChecker,
        CacheProvider $cache,
        MetadataProviderInterface $ownershipMetadataProvider,
        TokenStorageInterface $tokenStorage
    )
  ```
- Class `Oro\Bundle\CustomerBundle\Twig\CustomerExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $securityProvider`
- Added Configurable Permission `commerce` for View and Edit pages of Customer Role in backend area (see [configurable-permissions.md](../platform/src/Oro/Bundle/SecurityBundle/Resources/doc/configurable-permissions.md) for details.
- Added Configurable Permission `commerce_frontend` for View and Edit pages of Customer Role in frontend area (see [configurable-permissions.md](../platform/src/Oro/Bundle/SecurityBundle/Resources/doc/configurable-permissions.md) for details.

CheckoutBundle
--------------
- Class `Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository`:
    - added third argument `string $workflowName` for method `public function findCheckoutByCustomerUserAndSourceCriteria()`
- Class `Oro\Bundle\CheckoutBundle\Twig\LineItemsExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $totalsProvider`
    - removed property `protected $lineItemSubtotalProvider`

CommerceMenuBundle
------------------
- Class `Oro\Bundle\CommerceMenuBundle\Twig\MenuExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter

FlatRateBundle
-------------------
- Change name of the bundle to FlatRateShippingBundle

WebsiteSearchBundle
-------------------
- Driver::writeItem() and Driver::flushWrites() should be used instead of Driver::saveItems()

FrontendTestFrameworkBundle
---------------------------
- 'Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase' method `tearDown` renamed to `afterFrontendTest`

RFPBundle
---------
* The following classes were removed:
    - `Oro\Bundle\RFPBundle\Datagrid\ActionPermissionProvider`
    - `Oro\Bundle\RFPBundle\Entity\Repository\RequestStatusRepository`
* Removed controllers `RequestStatusController`
* The following fields and methods were removed from `Request` entity:
    - methods `setStatus`/`getStatus`
* Added enum fields `customer_status` and `internal_status` to `Oro\Bundle\RFPBundle\Entity\Request` entity
* Following methods were added to `Oro\Bundle\RFPBundle\Entity\Request` entity:
    - `getRequestAdditionalNotes`
    - `addRequestAdditionalNote`
    - `removeRequestAdditionalNote`
* Added new entities:
    - `Oro\Bundle\RFPBundle\Entity\RequestAdditionalNote`
* Removed entities:
    - `Oro\Bundle\RFPBundle\Entity\RequestStatus`
* Removed following classes:
    - `Oro\Bundle\RFPBundle\Form\Type\RequestStatusTranslationType`
    - `Oro\Bundle\RFPBundle\Form\Type\DefaulRequestStatusType`
    - `Oro\Bundle\RFPBundle\Form\Type\RequestStatusSelectType`
    - `Oro\Bundle\RFPBundle\Form\Type\RequestStatusWithDeletedSelectType`
* The methods `setRequestStatusClass` and `postSubmit` was removed from class `Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestType`

MoneyOrderBundle
----------------
* MoneyOrder implementation was changed using IntegrationBundle (refer to PaymentBundle and IntegrationBundle for details). Notable changes:
    - Class `Oro\Bundle\MoneyOrderBundle\DependencyInjection\Configuration` was removed and instead `Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings` was created - entity that implements `Oro\Bundle\IntegrationBundle\Entity\Transport` to store payment integration properties
    - Class `Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig` was realized as ParameterBag and is being used for holding payment integration properties that are stored in MoneyOrderSettings
    - Class `Oro\Bundle\MoneyOrderBundle\Method\MoneyOrder` method getIdentifier now uses MoneyOrderConfig to retrieve identifier of a concrete method
    - Class `Oro\Bundle\MoneyOrderBundle\Method\View\MoneyOrderView` now has two additional methods due to implementing `Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface`
        - getAdminLabel() is used to display labels in admin panel
        - getPaymentMethodIdentifier() used to properly display different methods in frontend
    - According to changes in PaymentBundle were added:
        - `Oro\Bundle\MoneyOrderBundle\Method\Provider\MoneyOrderMethodProvider` for providing *Money Order Payment Methods*
        - `Oro\Bundle\MoneyOrderBundle\Method\View\Provider\MoneyOrderMethodViewProvider` for providing *Money Order Payment Method Views*
    - Added multiple classes to implement payment through integration and most of them have interfaces, so they are extendable through composition:
        - `Oro\Bundle\MoneyOrderBundle\Form\Type\MoneyOrderSettingsType`
        - `Oro\Bundle\MoneyOrderBundle\Integration\MoneyOrderChannelType`
        - `Oro\Bundle\MoneyOrderBundle\Integration\MoneyOrderTransport`
        - `Oro\Bundle\MoneyOrderBundle\Method\Config\Factory\MoneyOrderConfigFactory`
        - `Oro\Bundle\MoneyOrderBundle\Method\Config\Provider\MoneyOrderConfigProvider`
        - `Oro\Bundle\MoneyOrderBundle\Method\Factory\MoneyOrderPaymentMethodFactory`
        - `Oro\Bundle\MoneyOrderBundle\Method\View\Factory\MoneyOrderPaymentMethodViewFactory`

OrderBundle
-----------
- Class `Oro\Bundle\OrderBundle\Twig\OrderExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $sourceDocumentFormatter`
    - removed property `protected $shippingTrackingFormatter`
- Class `Oro\Bundle\OrderBundle\Twig\OrderShippingExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed method `setShippingLabelFormatter`
- Class `Oro\Bundle\OrderBundle\EventListener\Order\OrderPossibleShippingMethodsEventListener` 
    - renamed and moved to `Oro\Bundle\OrderBundle\EventListener\PossibleShippingMethodEventListener`
    - constructor accepts `Oro\Bundle\ShippingBundle\Context\ShippingContextFactoryInterface` instead of `Oro\Bundle\OrderBundle\Factory\OrderShippingContextFactory`
    - method `onOrderEvent` renamed to `onEvent` and it accepts `Oro\Bundle\ShippingBundle\EventListener\EntityDataAwareEventInterface`

PaymentBundle
-------------
- Class `Oro\Bundle\PaymentBundle\Twig\PaymentMethodExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $paymentTransactionProvider`
    - removed property `protected $paymentMethodLabelFormatter`
    - removed property `protected $dispatcher`
- Class `Oro\Bundle\PaymentBundle\Twig\PaymentStatusExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $paymentStatusLabelFormatter`
- Abstracted and moved classes that relate to actions that disable/enable `Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule` to `RuleBundle` (refer to `RuleBundle` upgrade documentation)
    - removed `Oro\Bundle\PaymentBundle\Datagrid\Extension\MassAction\Actions\StatusDisableMassAction` and switched definition to `Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction`
    - removed `Oro\Bundle\PaymentBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction` and switched definition to `Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction`
    - removed `Oro\Bundle\PaymentBundle\Datagrid\Extension\MassAction\StatusMassActionHandler` and switched definition to `Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\StatusMassActionHandler`
    - removed `Oro\Bundle\PaymentBundle\Datagrid\PaymentRuleActionsVisibilityProvider` and switched definition to `Oro\Bundle\RuleBundle\Datagrid\RuleActionsVisibilityProvider`
- Abstracted and moved classes that relate to decorating `Oro\Bundle\ProductBundle\Entity\Product` with virtual fields to `ProductBundle` (refer to `ProductBundle` upgrade documentation)
    - removed `Oro\Bundle\PaymentBundle\QueryDesigner\SelectQueryConverter`
    - removed `Oro\Bundle\PaymentBundle\QueryDesigner\PaymentProductQueryDesigner`
    - removed `Oro\Bundle\PaymentBundle\ExpressionLanguage\ProductDecorator`
    - class `Oro\Bundle\PaymentBundle\ExpressionLanguage\DecoratedProductLineItemFactory` only dependency is now `Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory`
- Entity `Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule`
    - added ownership type *organization*

* In order to have possibility to create more than one payment method of same type PaymentBundle was significantly changed **with breaking backwards compatibility**.
    - To realize this was added `Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface` which should be implemented in any class(payment method provider) which is responsible for providing of any payment method.
    - Also was added `Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface` which should be implemented in any class(payment method view provider) which is responsible for providing of any payment method view.
    - Class `Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry` was changed to `Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistry` which implements `Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface` and this registry is responsible for collecting data from all payment method providers
    - Class `Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry` was changed to `Oro\Bundle\PaymentBundle\Method\View\CompositePaymentMethodViewProvider` which implements `Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface` this composite provider is single point to provide data from all payment method view providers
    - Any payment method provider should be registered in service definitions with tag *oro_payment.payment_method_provider*
    - Any payment method view provider should be registered in service definitions with tag *oro_payment.payment_method_view_provider*
    - Each payment method provider should provide payment method(one or many) which should implement `Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface`.
    - Each payment method view provider should provide payment method view(one or many) which should implement `Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface`.
    - In order to keep all common logic of all payment method providers was created `Oro\Bundle\PaymentBundle\Method\Provider\AbstractPaymentMethodProvider` which should be extended by any payment method provider
    - In order to keep all common logic of all payment method view providers was created `Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider` which should be extended by any payment method view provider
    - Class`Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface` was modified:
        - removed methods:
            - getType()
        - added methods:
            - getIdentifier()
    - Class`Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface` was modified:
        - removed methods:
            - getPaymentMethodType()
        - added methods:
            - getAdminLabel()
            - getPaymentMethodIdentifier()
    - Class`Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface` was modified:
        - added methods:
            - getAdminLabel()
            - getPaymentMethodIdentifier()

PaymentTermBundle
-----------------
- Class `Oro\Bundle\PaymentTermBundle\Twig\DeleteMessageTextExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $deleteMessageGenerator`
    
* PaymentTerm implementation was changed using IntegrationBundle (refer to PaymentBundle and IntegrationBundle for details). Notable changes:
    - Class `Oro\Bundle\PaymentTermBundle\DependencyInjection\Configuration` was removed and instead `Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings` was created - entity that implements `Oro\Bundle\IntegrationBundle\Entity\Transport` to store payment integration properties
    - Class `Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfig` was removed and instead simple parameter bag object `Oro\Bundle\PaymentTermBundle\Method\Config\ParameterBagPaymentTermConfig` is being used for holding payment integration properties that are stored in PaymentTermSettings
    - Class `Oro\Bundle\PaymentTermBundle\Method\PaymentTerm` method getIdentifier now uses PaymentTermConfig to retrieve identifier of a concrete method
    - Class `Oro\Bundle\PaymentTermBundle\Method\View\PaymentTermView` now has two additional methods due to implementing `Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface`
        getAdminLabel() is used to display labels in admin panel
        getPaymentMethodIdentifier() used to properly display different methods in frontend
    - Added multiple classes to implement payment through integration and most of them have interfaces, so they are extendable through composition:
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

PayPalBundle
-------------
* PayPalBundle implementation was changed using IntegrationBundle (refer to PaymentBundle and IntegrationBundle for details). Notable changes:
    - Class `Oro\Bundle\PayPalBundle\DependencyInjection\Configuration` was removed and instead `Oro\Bundle\PayPalBundle\Entity\PayPalSettings` was created - entity that implements `Oro\Bundle\IntegrationBundle\Entity\Transport` to store paypal payment integration properties
    - Classes `Oro\Bundle\PayPalBundle\Method\Config\PayflowExpressCheckoutConfig`, `Oro\Bundle\PayPalBundle\Method\Config\PayPalPaymentsProExpressCheckoutConfig` were removed and instead simple parameter bag object `Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfig` is being used for holding payment integration properties that are stored in PayPalSettings
    - Classes `Oro\Bundle\PayPalBundle\Method\Config\PayflowGatewayConfig`, `Oro\Bundle\PayPalBundle\Method\Config\PayPalPaymentsProConfig` were removed and instead simple parameter bag object `Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig` is being used for holding payment integration properties that are stored in PayPalSettings
    - Classes `Oro\Bundle\PayPalBundle\Method\PayflowExpressCheckout`, `Oro\Bundle\PayPalBundle\Method\PayPalPaymentsProExpressCheckout` were removed and instead was added `Oro\Bundle\PayPalBundle\Method\PayPalExpressCheckoutPaymentMethod`
    - Classes `Oro\Bundle\PayPalBundle\Method\PayflowGateway`, `Oro\Bundle\PayPalBundle\Method\PayPalPaymentsPro` were removed and instead was added `Oro\Bundle\PayPalBundle\Method\PayPalCreditCardPaymentMethod`
    - Classes `Oro\Bundle\PayPalBundle\Method\View\PayflowExpressCheckout`, `Oro\Bundle\PayPalBundle\Method\View\PayPalPaymentsProExpressCheckout` were removed and instead was added `Oro\Bundle\PayPalBundle\Method\View\PayPalExpressCheckoutPaymentMethodView`
    - Classes `Oro\Bundle\PayPalBundle\Method\View\PayflowGateway`, `Oro\Bundle\PayPalBundle\Method\View\PayPalPaymentsPro` were removed and instead was added `Oro\Bundle\PayPalBundle\Method\View\PayPalCreditCardPaymentMethodView`
    - According to changes in PaymentBundle were added:
        - `Oro\Bundle\PayPalBundle\Method\Provider\CreditCardMethodProvider` for providing *PayPal Credit Card Payment Methods*
        - `Oro\Bundle\PayPalBundle\Method\View\Provider\CreditCardMethodViewProvider` for providing *PayPal Credit Card Payment Method Views*
        - `Oro\Bundle\PayPalBundle\Method\Provider\ExpressCheckoutMethodProvider` for providing *PayPal Express Checkout Payment Methods*
        - `Oro\Bundle\PayPalBundle\Method\View\Provider\ExpressCheckoutMethodViewProvider` for providing *PayPal Express Checkout Payment Method Views*
    - Added multiple classes to implement payment through integration and most of them have interfaces, so they are extendable through composition:
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

PricingBundle
-------------
- Class `Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository`
    - changed the return type of `getCombinedPriceListsByPriceList` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    - changed the return type of `getCombinedPriceListsByPriceLists` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    - changed the return type of `getCPLsForPriceCollectByTimeOffset` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
- Class `Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerFallbackRepository`
    - changed the return type of `getCustomerIdentityByGroup` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
- Class `Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerGroupFallbackRepository`
    - changed the return type of `getCustomerIdentityByWebsite` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
- Class `Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository`
    - changed the return type of `getPriceListsWithRules` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
- Class `Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository`
    - changed the return type of `getCustomerGroupIteratorByDefaultFallback` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    - changed the return type of `getIteratorByPriceList` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
- Class `Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository`
    - changed the return type of `getCustomerIteratorByDefaultFallback` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    - changed the return type of `getCustomerWebsitePairsByCustomerGroupIterator` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
    - changed the return type of `getIteratorByPriceList` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
- Class `Oro\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository`
    - changed the return type of `getWebsiteIteratorByDefaultFallback` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`
- Class `Oro\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType`
    - field `priority` was removed. Field `_position` from `Oro\Bundle\FormBundle\Form\Extension\SortableExtension` will be used instead.
- Class `Oro\Bundle\PricingBundle\Entity\BasePriceListRelation`
    - property `$priority` was renamed to `$sortOrder`
    - methods `getPriority` and `setPriority` were renamed to `getSortOrder` and `setSortOrder` accordingly
- Class `Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig`
    - property `$priority` was renamed to `$sortOrder`
    - methods `getPriority` and `setPriority` were renamed to `getSortOrder` and `setSortOrder` accordingly
- Interface `Oro\Bundle\PricingBundle\Entity\PriceListAwareInterface`
    - method `getPriority` was renamed to `getSortOrder`
- Class `Oro\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter`
    - constant `PRIORITY_KEY` was renamed to `SORT_ORDER_KEY`
    
ProductBundle
-------------
- Class `Oro\Bundle\ProductBundle\Twig\ProductExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
- Class `Oro\Bundle\ProductBundle\Twig\ProductUnitFieldsSettingsExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $productUnitFieldsSettings`
- Class `Oro\Bundle\ProductBundle\Twig\ProductUnitLabelExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $formatter`
- Class `Oro\Bundle\ProductBundle\Twig\ProductUnitValueExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $formatter`
- Class `Oro\Bundle\ProductBundle\Twig\UnitVisibilityExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $unitVisibility`
- Added classes that can decorate `Oro\Bundle\ProductBundle\Entity\Product` to have virtual fields
    - `Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory` is the class that should be used to create a decorated `Product`
    - `Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecorator` is the class that decorates `Product`
    - `Oro\Bundle\ProductBundle\VirtualFields\QueryDesigner\VirtualFieldsSelectQueryConverter` this converter is used inside of `VirtualFieldsProductDecorator`
    - `Oro\Bundle\ProductBundle\VirtualFields\QueryDesigner\VirtualFieldsProductQueryDesigner` this query designer is used inside of `VirtualFieldsProductDecorator`
- Removed constructor of `Oro\Bundle\ProductBundle\Form\Type\ProductPageVariantType`.
    - corresponding logic moved to `Oro\Bundle\WebCatalogBundle\Form\Extension\PageVariantTypeExtension`

SaleBundle
----------
- Class `Oro\Bundle\SaleBundle\Twig\QuoteExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $quoteProductFormatter`
    - removed property `protected $configManager`
- Class `Oro\Bundle\SaleBundle\EventListener\Quote\QuotePossibleShippingMethodsEventListener` removed. 
    - `Oro\Bundle\OrderBundle\EventListener\PossibleShippingMethodEventListener` must be used instead.

ShoppingListBundle
------------------
- Class `Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository`
    - changed signature of `invalidateTotals` method from `invalidateTotals(BufferedQueryResultIterator $iterator)` to `invalidateTotals(\Iterator $iterator)`
- Class `Oro\Bundle\ShippingBundle\Twig\DimensionsUnitValueExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $formatter`
- Class `Oro\Bundle\ShippingBundle\Twig\ShippingMethodExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $shippingMethodLabelFormatter`
    - removed property `protected $dispatcher`
- Class `Oro\Bundle\ShippingBundle\Twig\ShippingOptionLabelExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $lengthUnitLabelFormatter`
    - removed property `protected $weightUnitLabelFormatter`
    - removed property `protected $freightClassLabelFormatter`
- Class `Oro\Bundle\ShippingBundle\Twig\WeightUnitValueExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $formatter`

VisibilityBundle
----------------
- Class `Oro\Bundle\VisibilityBundle\Driver\AbstractCustomerPartialUpdateDriver`
    - changed the return type of `getCustomerVisibilityIterator` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`

RuleBundle
----------
- Added `Oro\Bundle\RuleBundle\Entity\RuleInterface` this interface should now be used for injection instead of `Rule` in bundles that implement `RuleBundle` functionality
- Added classes for handling enable/disable `Rule` actions - use them to define corresponding services
    - `Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\StatusMassActionHandler`
    - `Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction`
    - `Oro\Bundle\RuleBundle\Datagrid\RuleActionsVisibilityProvider`
- Added `RuleActionsVisibilityProvider` that should be used to define action visibility configuration in datagrids with `Rule` entity fields

ShippingBundle
--------------
- Abstracted and moved classes that relate to actions that disable/enable `Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule` to `RuleBundle` (refer to `RuleBundle` upgrade documentation)
    - removed `Oro\Bundle\ShippingBundle\Datagrid\Extension\MassAction\Actions\StatusDisableMassAction` and switched definition to `Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction`
    - removed `Oro\Bundle\ShippingBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction` and switched definition to `Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\Actions\StatusEnableMassAction`
    - removed `Oro\Bundle\ShippingBundle\Datagrid\Extension\MassAction\StatusMassActionHandler` and switched definition to `Oro\Bundle\RuleBundle\Datagrid\Extension\MassAction\StatusMassActionHandler`
    - removed `Oro\Bundle\ShippingBundle\Datagrid\ShippingRuleActionsVisibilityProvider` and switched definition to `Oro\Bundle\RuleBundle\Datagrid\RuleActionsVisibilityProvider`
- Abstracted and moved classes that relate to decorating `Oro\Bundle\ProductBundle\Entity\Product` with virtual fields to `ProductBundle` (refer to `ProductBundle` upgrade documentation)
    - removed `Oro\Bundle\ShippingBundle\QueryDesigner\SelectQueryConverter`
    - removed `Oro\Bundle\ShippingBundle\QueryDesigner\ShippingProductQueryDesigner`
    - removed `Oro\Bundle\ShippingBundle\ExpressionLanguage\ProductDecorator`
    - class `Oro\Bundle\PaymentBundle\ExpressionLanguage\DecoratedProductLineItemFactory` only dependency is now `Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory`

WebCatalogBundle
----------------
- Class `Oro\Bundle\WebCatalogBundle\Twig\WebCatalogExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $treeHandler`
    - removed property `protected $contentVariantTypeRegistry`

WebsiteBundle
-------------
- Class `Oro\Bundle\WebsiteBundle\Twig\OroWebsiteExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $websiteManager`
- Class `Oro\Bundle\WebsiteBundle\Twig\WebsitePathExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $websiteUrlResolver`

RedirectBundle
--------------
- `Oro\Bundle\RedirectBundle\Entity\Redirect`
    - removed property `website` in favour of `scopes` collection using

CMSBundle
---------
- Removed constructor of `Oro\Bundle\CMSBundle\Form\Type\CmsPageVariantType`.
    - corresponding logic moved to `Oro\Bundle\WebCatalogBundle\Form\Extension\PageVariantTypeExtension`
