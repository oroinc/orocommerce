UPGRADE FROM 1.0.0-BETA.5 to 1.0.0-RC.1
=======================================

General
-------
* Changed minimum required `php` version to **5.6**
* Upgrade from **1.0.0-beta.1**,  **1.0.0-beta.2**, **1.0.0-beta.3** or **1.0.0-beta.4** use command:
```bash
php app/console oro:platform:upgrade20 --env=prod --force
```

* Upgrade from **1.0.0-beta.5** use command:
```bash
php app/console oro:platform:update --env=prod --force
```

AlternativeCheckoutBundle
-------------------------
* The `AlternativeCheckoutByQuote` class was removed.

CatalogBundle
-------------
* The classes `CategoryMessageHandler` and `CategoryMessageFactory` was removed.
* The following methods were added to `Category` entity:
    - `hasProduct`
    - `getSlugPrototypes`/`addSlugPrototype`/`removeSlugPrototype`/`hasSlugPrototype`
    - `getSlugs`/`addSlug`/`removeSlug`/`resetSlugs`/`hasSlug`
* New methods `preFlush` and `onClear` were added to class `ProductStrategyEventListener`.
* The methods `setEntityClass` and `setDefaultOptions` was removed from class `CategoryTreeType`.
* New methods `__construct` and `configureOptions` were added to class `CategoryTreeType`.

CheckoutBundle
--------------
* The classes `ShippingContextProviderFactory`, `CheckoutEntityListener` was removed.
* The class `StartCheckout` and action `@start_checkout` was removed.
* Operations `oro_shoppinglist_frontend_createorder` and `oro_shoppinglist_frontend_products_quick_add_to_checkout` was removed.
* The `DefaultShippingMethodSetter::__construct` method has been updated. Pass `CheckoutShippingContextFactory` as a first argument of the method instead of `ShippingContextProviderFactory`.
* The `CheckoutShippingContextProvider::__construct` method has been updated. Pass `CheckoutShippingContextFactory` as a first argument of the method instead of `ShippingContextProviderFactory`.
* The `CheckoutRepository::getCheckoutByQuote` method has been updated. Pass `\Oro\Bundle\CustomerBundle\Entity\AccountUser` as a second argument of the method.
* New method `findCheckoutByAccountUserAndSourceCriteria` were added to class `CheckoutRepository`.
* The `ResolvePaymentTermListener::__construct` method has been updated. Pass `Doctrine\Common\Persistence\ManagerRegistry` as a second argument of the method instead of `Symfony\Component\EventDispatcher\EventDispatcherInterface`. Pass `Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider` as a third argument of the method.
* The `ResolvePaymentTermListener::onResolvePaymentTerm` method has been updated. Pass `Oro\Bundle\PaymentTermBundle\Event\ResolvePaymentTermEvent` as a first argument of the method instead of `Oro\Bundle\PaymentBundle\Event\ResolvePaymentTermEvent`.
* The method `getCheckout` was removed from class `CheckoutController`.
* New protected methods `isCheckoutRestartRequired` and `restartCheckout` were added to class `CheckoutController`.
* The `OrderMapper::__construct` method has been updated. Pass `Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider` as a third argument of the method.
* New protected method `assignPaymentTerm` were added to class `OrderMapper`.
* The `ShippingMethodEnabledMapper::__construct` method has been updated. Pass `CheckoutShippingContextFactory` as a second argument of the method instead of `ShippingContextProviderFactory`.
* The `ShippingMethodDiffMapper::__construct` method has been updated. Pass `CheckoutShippingContextFactory` as a second argument of the method instead of `ShippingContextProviderFactory`.
* New method `finishView` were added to class `CheckoutAddressType`.
* The `CheckoutInterface::getSourceEntity` method should return `CheckoutSourceEntityInterface` or `null`.

CMSBundle
---------
* The classes `PageSlugListener`, `PageHandler` and `SlugType` was removed.
* The following methods was removed from `Page` entity:
    - `getTitle`/`setTitle`
    - `setCurrentSlug`/`getCurrentSlug`
    - `setCurrentSlugUrl`/`getCurrentSlugUrl`
    - `getRelatedSlugs`
    - `refreshSlugUrls`
* The following methods were added to `Page` entity:
    - `getTitles`/`addTitle`/`removeTitle`
    - `getSlugPrototypes`/`addSlugPrototype`/`removeSlugPrototype`/`hasSlugPrototype`/`resetSlugs`/`hasSlug`

CustomerBundle
--------------
* The entity `AccountGroup` now is extendable.
* The classes `AccountMessageFactory` and `AccountUserRoleController` was removed.
* The `AccountUserController::getRolesAction` method has been updated. Pass `Symfony\Component\HttpFoundation\Request` as a first argument of the method.
* The methods `getNoOwnershipMetadata` and `getSystemLevelClass` was removed from class `FrontendOwnershipMetadataProvider`.
* New protected method `createNoOwnershipMetadata` were added to class `FrontendOwnershipMetadataProvider`.
* New method `getAccountUserSelectFormView` were added to class `FrontendAccountUserFormProvider`.
* New methods `isGrantedViewDeep` and `isGrantedViewSystem` were added to class `AccountUserProvider`.
* The `AccountRepository::getChildrenIds` method has been updated. Pass `Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper` as a second argument of the method instead of `int`.
* The methods `getCustomer` and `setCustomer` was removed from class `AccountUser`.
* The `AccountDatagridListener::__contruct` method has been updated. Pass `array` as a third argument of the method.
* The `AccountDatagridListener::showAllAccountItems` method has been updated. Pass `boolean` as a second argument of the method.
* New protected method `permissionShowAllAccountItemsForChild` were added to class `AccountDatagridListener`.
* The method `onBuildBefore` was removed from class `AccountUserRoleDatagridListener`.
* The `AccountUserRoleDatagridListener::__construct` method has been updated. Pass `Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper` as a first argument of the method instead of `Oro\Bundle\SecurityBundle\SecurityFacade`.
* New method `onBuildAfter` were added to class `AccountUserRoleDatagridListener`.
* The `AclPermissionController::aclAccessLevelsAction` method has been updated. Pass `string` as a second argument of the method.
* New method `preSetData` were added to class `FrontendAccountUserRoleType`.
* New method `preSetData` were added to class `FrontendAccountUserProfileType`.
* The `FrontendAccountUserRoleSelectType::__construct` method has been updated. Pass `Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper` as a third argument of the method.
* The method `isGrantedAccountUserRole` was removed from class `AccountUserRoleVoter`.
* New method `__construct` were added to class `AccountVoter`.

CommerceMenuBundle
------------------
* The `AccountOwnershipProvider` class was removed.
* The following methods was removed from `MenuUpdate` entity:
    - `getOwnershipType`/`setOwnershipType`
    - `getOwnerId`/`setOwnerId`
* New methods `getScope`/`setScope` were added to `MenuUpdate` entity.

FrontendBundle
--------------
* The `ActionApplicationsHelper` class was removed. Use `ActionCurrentApplicationProvider` and `RouteProvider` instead.
* The `FrontendController::exceptionAction` method has been updated. Pass `Symfony\Component\HttpFoundation\Request` as a first argument of the method instead of `int`. Pass `string` as a third argument of the method.
* New method `isFrontendUrl` were added to class `FrontendHelper`.
* Changed priority for `oro_frontend.listener.datagrid.fields` and `oro_frontend.listener.enum_filter_frontend_listener`.

InventoryBundle
---------------
* The method `__construct` was removed from class `CategoryManageInventoryFormViewListener`.
* The methods `__construct` and `getProductFromRequest` was removed from class `ProductManageInventoryFormViewListener`.

MenuBundle
----------
* The `MenuBundle` bundle was removed. All logic replaced to [`CommerceMenuBundle`](#commercemenubundle) bundle.

MoneyOrderBundle
----------------
* The methods `__construct` and `isEnabled` was removed from class `MoneyOrder`.
* The `MoneyOrder::isApplicable` method has been updated. Pass `PaymentContextInterface` as a first argument of the method.
* The `MoneyOrder::getOptions` method has been updated. Pass `PaymentContextInterface` as a first argument of the method.
* The following methods was removed from class `MoneyOrderConfig`:
    - `getAllowedCountries`
    - `getAllowedCurrencies`
    - `isAllCountriesAllowed`
    - `isEnabled`
    - `getOrder`
    - `isCountryApplicable`
    - `isCurrencyApplicable`
* The method `getOrder` was removed from class `MoneyOrderView`.

OrderBundle
-----------
* The following classes was removed:
    - `OrderShippingMethodProvider`
    - `ShippingMethodFormatter`
    - `AjaxOrderController`
    - `OrderController`
    - `FrontendOrderType`
    - `FrontendOrderLineItemType`
* The `OrderShippingContextFactory::__construct` method has been updated. Pass `OrderShippingLineItemConverterInterface` as a second argument of the method instead of `Oro\Bundle\ShippingBundle\Factory\ShippingContextFactor`. Pass `Oro\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface` as a third argument of the method.
* The `OrderExtension::__construct` method has been updated. Third argument was removed.
* The `OrderTotalsHandler::__construct` method has been updated. Pass `Oro\Bundle\CurrencyBundle\Converter\RateConverterInterface` as a third argument of the method.
* The `OrderCurrencyHandler::__construct` method has been updated. Pass `Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface` as a first argument of the method instead of `Oro\Bundle\LocaleBundle\Model\LocaleSettings`.
* The methods `setPaymentTerm` and `getPaymentTerm` was removed from `Order` entity. Use `oro_payment_term.provider.payment_term_association` to assign `PaymentTerm` to entity
* The following methods were added to `Order` entity:
    - `getBaseSubtotalValue`/`setBaseSubtotalValue`
    - `getSubtotalObject`/`setSubtotalObject`
    - `getBaseTotalValue`/`setBaseTotalValue`
    - `getTotalObject`/`setTotalObject`
    - `loadMultiCurrencyFields`/`updateMultiCurrencyFields`
* The following protected methods were added to `Order` entity:
    - `fixCurrencyInMultiCurrencyFields`
    - `setSubtotalCurrency`
    - `setTotalCurrency`
    - `updateSubtotal`
    - `updateTotal`
* The `TotalCalculateListener::__construct` method has been updated. Pass `Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface` as a second argument of the method instead of `Oro\Bundle\ActionBundle\Helper\ApplicationsHelper`.
* The `OrderPaymentTermEventListener::__construct` method has been updated. Pass `Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider` as a first argument of the method instead of `Oro\Bundle\PaymentBundle\Provider\PaymentTermProvider`.
* The `OrderTotalEventListener::__construct` method has been updated. Pass `Oro\Bundle\OrderBundle\Provider\TotalProvider` as a first argument of the method instead of `Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider`.
* The method `infoAction` was removed from class `OrderController`.
* The `FrontendOrderDataHandler::__construct` method has been updated. Pass `Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider` as a fourth argument of the method instead of `Oro\Bundle\PaymentBundle\Provider\PaymentTermProvider`.
* The following methods was removed from class `OrderType`:
    - `isOverridePaymentTermGranted`
    - `getAccountPaymentTermId`
    - `getAccountGroupPaymentTermId`
    - `addPaymentTerm`
- Changed name from `securityFacade` to `orderAddressSecurityProvider`, type from `Oro\Bundle\SecurityBundle\SecurityFacade` to `Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider` of first argument of `Oro\Bundle\OrderBundle\Form\Type\OrderType` constructor
- Changed name from `orderAddressSecurityProvider` to `orderCurrencyHandler`, type from `Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider` to `Oro\Bundle\OrderBundle\Handler\OrderCurrencyHandler` of second argument of `Oro\Bundle\OrderBundle\Form\Type\OrderType` constructor
- Changed name from `paymentTermProvider` to `subtotalSubscriber`, type from `Oro\Bundle\PaymentBundle\Provider\PaymentTermProvider` to `Oro\Bundle\OrderBundle\Form\Type\EventListener\SubtotalSubscriber` of third argument of `Oro\Bundle\OrderBundle\Form\Type\OrderType` constructor
- `Oro\Bundle\OrderBundle\Form\Type\OrderType` `SecurityFacade $securityFacade` and `PaymentTermProvider $paymentTermProvider` removed, use `\Oro\Bundle\PaymentTermBundle\Form\Extension\PaymentTermAclExtension` instead
- Added fourth argument `rateConverter` to `Oro\Bundle\OrderBundle\Total\TotalHelper` constructor

PaymentBundle
-------------
* All code related to `PaymentTerm` moved to `PaymentTermBundle`. Significant changes listed below.
* The following classes was moved to `PaymentTermBundle`:
    - `DeleteMessageTextExtension`
    - `DeleteMessageTextGenerator`
    - `PaymentTermProvider`
    - `ResolvePaymentTermEvent`
    - `PaymentTerm`
    - `FormViewListener`
    - `DatagridListener`
    - `PaymentTermController`
    - `PaymentTermConfig`
    - `PaymentTermView`
    - `AccountGroupFormExtension`
    - `AccountFormExtension`
    - `PaymentTermType`
    - `PaymentTermSelectType`
* The following classes was removed:
    - `PaymentMethodsProvider`
    - `PaymentMethodEnabled`
    - `CurrencyConfigAwareInterface`
    - `PaymentTermRepository`
    - `AbstractPaymentTermExtension`
    - `PaymentTermHandler`
* The methods `isEnabled` and `getOrder` was removed from interface `PaymentConfigInterface`.
* The method `isEnabled` was removed from interface `PaymentMethodInterface`.
* The `PaymentMethodInterface::isApplicable` method has been updated. Pass `PaymentContextInterface` as a first argument of the method.
* The method `getOrder` was removed from interface `PaymentMethodViewInterface`.
* The `PaymentMethodViewInterface::getOptions` method has been updated. Pass `PaymentContextInterface` as a first argument of the method.
* The method `getName` was removed from class `PaymentMethodExtension`.
* New method `getPaymentMethodConfigRenderData` were added to class `PaymentMethodExtension`.
* The `PaymentMethodExtension::__construct` method has been updated. Pass `Symfony\Component\EventDispatcher\EventDispatcherInterface` as a third argument of the method.
* The `PaymentMethodApplicable::__construct` method has been updated. Pass `PaymentMethodProvider` as a first argument of the method instead of `PaymentMethodRegistry`. Second argument of the method was removed.
* The `HasApplicablePaymentMethods::__construct` method has been updated. Pass `PaymentMethodProvider` as a first argument of the method instead of `PaymentMethodRegistry`. Second argument of the method was removed.
* The method `__construct` was removed from class `PaymentMethodViewRegistry`.

PayPalBundle
------------
* The `PayPalSelectedCountriesListener` class was removed.
* The methods `isEnabled` and `getOrder` was removed from class `PayflowExpressCheckoutConfig`.
* The following methods was removed from class `PayflowGatewayConfig`:
    - `getAllowedCountries`
    - `isAllCountriesAllowed`
    - `getAllowedCurrencies`
    - `isEnabled`
    - `getOrder`
    - `isCountryApplicable`
    - `isCurrencyApplicable`
* The methods `isEnabled` and `getOrder` was removed from class `PayPalPaymentsProExpressCheckoutConfig`.
* The following methods was removed from class `PayPalPaymentsProConfig`:
    - `getAllowedCountries`
    - `isAllCountriesAllowed`
    - `getAllowedCurrencies`
    - `isEnabled`
    - `getOrder`
    - `isCountryApplicable`
    - `isCurrencyApplicable`
* The method `getOrder` was removed from class `PayflowGatewayView`.
* The `PayflowGatewayView::getOptions` method has been updated. Pass `PaymentContextInterface` as a first argument of the method.
* The method `getOrder` was removed from class `PayflowExpressCheckoutView`.
* The `PayflowExpressCheckoutView::getOptions` method has been updated. Pass `PaymentContextInterface` as a first argument of the method.
* The method `isEnabled` was removed from class `PayflowGateway`.
* The `PayflowGateway::isApplicable` method has been updated. Pass `PaymentContextInterface` as a first argument of the method.
* The method `isEnabled` was removed from class `PayflowExpressCheckout`.
* The `PayflowExpressCheckout::isApplicable` method has been updated. Pass `PaymentContextInterface` as a first argument of the method.
- Added method `configureOptions` to class `Oro\Bundle\PayPalBundle\Form\Type\CurrencySelectionType`

PricingBundle
-------------
* The following classes was removed:
    - `DefaultCurrency`
    - `DefaultCurrencyValidator`
    - `SystemCurrencyFormExtension`
    - `DefaultCurrencySelectionType`
- Added seventh argument `triggerHandler` to `Oro\Bundle\PricingBundle\Builder\AbstractCombinedPriceListBuilder` constructor
- Added third argument `triggerHandler` to `Oro\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector` constructor
- Added method `getId` to class `Oro\Bundle\PricingBundle\Entity\BaseCombinedPriceListRelation`
- Removed method `deleteUnusedPriceLists` from class `Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository`
- Removed method `getUnusedPriceListsIterator` from class `Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository`
* The following methods were added to class `CombinedPriceListRepository`:
    - `deletePriceLists`
    - `getUnusedPriceListsIds`
    - `hasOtherRelations`
    - `getProductIdsByPriceLists`
    - `findMinByWebsiteForFilter`
    - `findMinByWebsiteForSort`
- Added protected method `getQbForMinimalPrices` to class `Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository`
- Added fourth argument `translator` to `Oro\Bundle\PricingBundle\EventListener\FrontendProductPriceDatagridListener` constructor
- Added seventh argument `triggerHandler` to `Oro\Bundle\PricingBundle\Async\PriceListProcessor` constructor
- Changed name from `configManager` to `currencyProvider`, type from `Oro\Bundle\ConfigBundle\Config\ConfigManager` to `Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface` of third argument of `Oro\Bundle\PricingBundle\Manager\UserCurrencyManager` constructor
- Added third argument `triggerHandler` to `Oro\Bundle\PricingBundle\Resolver\CombinedProductPriceResolver` constructor
- Added third argument `triggerHandler` to `Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver` constructor
- Removed method `getPriceList` from class `Oro\Bundle\PricingBundle\Filter\FrontendProductPriceFilter`
- Removed method `apply` from class `Oro\Bundle\PricingBundle\Filter\FrontendProductPriceFilter`
- Removed method `setRegistry` from class `Oro\Bundle\PricingBundle\Filter\FrontendProductPriceFilter`
- Added protected method `getFieldName` to class `Oro\Bundle\PricingBundle\Filter\FrontendProductPriceFilter`
- Added method `setFormatter` to class `Oro\Bundle\PricingBundle\Filter\FrontendProductPriceFilter`
- Added method `getMetadata` to class `Oro\Bundle\PricingBundle\Filter\FrontendProductPriceFilter`
- Added protected method `getFormType` to class `Oro\Bundle\PricingBundle\Filter\FrontendProductPriceFilter`

ProductBundle
-------------
* New method `isConfigurableType` were added to class `ProductExtension`.
* New protected method `addTypeToConfig` were added to class `FrontendProductDatagridListener`.
* The classes `ProductMessageHandler`, `ProductMessageFactory` and `ProductCustomFieldsChoiceType` was removed.
- Removed method `getEntityFields` from class `Oro\Bundle\ProductBundle\Expression\FieldsProvider`
- Added method `addFieldToWhiteList` to class `Oro\Bundle\ProductBundle\Expression\FieldsProvider`
- Added method `addFieldToBlackList` to class `Oro\Bundle\ProductBundle\Expression\FieldsProvider`
- Added method `getDetailedFieldsInformation` to class `Oro\Bundle\ProductBundle\Expression\FieldsProvider`
- Added protected method `isSupportedRelation` to class `Oro\Bundle\ProductBundle\Expression\FieldsProvider`
- Added protected method `isWhitelistedField` to class `Oro\Bundle\ProductBundle\Expression\FieldsProvider`
- Added protected method `isBlacklistedField` to class `Oro\Bundle\ProductBundle\Expression\FieldsProvider`
- Added method `getVariantFieldsForm` to class `Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormProvider`
- Added method `getVariantFieldsFormView` to class `Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormProvider`
- Added method `getSimpleProductsByVariantFieldsQueryBuilder` to class `Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository`
- Removed method `getHasVariants` from class `Oro\Bundle\ProductBundle\Entity\Product`
- Removed method `setHasVariants` from class `Oro\Bundle\ProductBundle\Entity\Product`
* The following methods were added to `Product` entity:
    - `getTypes`
    - `isSimple`
    - `isConfigurable`
    - `getParentVariantLinks`/`addParentVariantLink`/`removeParentVariantLink`
    - `getType`/`setType`
    - `getAttributeFamily`/`setAttributeFamily`
    - `getSlugPrototypes`/`addSlugPrototype`/`removeSlugPrototype`/`hasSlugPrototype`
    - `getSlugs`/`addSlug`/`removeSlug`/`resetSlugs`/`hasSlug`
- Added method `__construct` to class `Oro\Bundle\ProductBundle\EventListener\ProductHandlerListener`
- Added protected method `clearCustomExtendVariantFields` to class `Oro\Bundle\ProductBundle\EventListener\ProductHandlerListener`
* The methods `onBuildBefore` and `onResultAfter` was removed from class `ProductVariantCustomFieldsDatagridListener`.
- Added fourth argument `productVariantLinkClass` to `Oro\Bundle\ProductBundle\EventListener\ProductVariantCustomFieldsDatagridListener` constructor
- Added method `onBuildBeforeHideUnsuitable` to class `Oro\Bundle\ProductBundle\EventListener\ProductVariantCustomFieldsDatagridListener`
- Added method `onBuildAfter` to class `Oro\Bundle\ProductBundle\EventListener\ProductVariantCustomFieldsDatagridListener`
- Added third argument `eventDispatcher` to `Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder` constructor
- Added method `buildCollectionBySkuFromFile` to class `Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder`
- Added method `setEventDispatcher` to class `Oro\Bundle\ProductBundle\Model\QuickAddRowCollection`
- Added method `getValidSkuRows` to class `Oro\Bundle\ProductBundle\Model\QuickAddRowCollection`
- Added method `addError` to class `Oro\Bundle\ProductBundle\Model\QuickAddRow`
- Added method `getErrors` to class `Oro\Bundle\ProductBundle\Model\QuickAddRow`
- Added method `hasErrors` to class `Oro\Bundle\ProductBundle\Model\QuickAddRow`
- Added method `__construct` to class `Oro\Bundle\ProductBundle\Validator\Constraints\UniqueProductVariantLinksValidator`
- Removed first argument `request` from method `Oro\Bundle\ProductBundle\Controller\Frontend\QuickAddController:copyPasteAction`
- Added type `Symfony\Component\HttpFoundation\Request` to first argument of method `Oro\Bundle\ProductBundle\Controller\ProductController:createStepTwo`
- Added method `setProductClass` to class `Oro\Bundle\ProductBundle\ImportExport\Strategy\ProductStrategy`
- Added protected method `importExistingEntity` to class `Oro\Bundle\ProductBundle\ImportExport\Strategy\ProductStrategy`
- Added protected method `configureFormOptions` to class `Oro\Bundle\ProductBundle\Form\Handler\QuickAddHandler`
- Added method `onPreSubmit` to class `Oro\Bundle\ProductBundle\Form\Type\QuickAddType`
- Added method `buildForm` to class `Oro\Bundle\ProductBundle\Form\Type\ProductStepOneType`

RedirectBundle
--------------
* The class `ForwardListener` was removed.
* The following methods were added to `Slug` entity:
    - `__construct`
    - `getScopes`/`addScope`/`removeScope`/`resetScopes`
    - `getRedirects`/`addRedirect`/`removeRedirect`
    - `getLocalization`/`setLocalization`

SaleBundle
----------
* New methods `getAccountUser`/`setAccountUser` and `getAccount`/`setAccount` were added to class `QuoteDemand`.
* New method `hasRecordsWithRemovingCurrencies` were added to class `QuoteRepository`.
* The methods `getPaymentTerm`/`setPaymentTerm` and `fillSubtotals` was removed from `Quote` entity. Use `oro_payment_term.provider.payment_term_association` to assign `PaymentTerm` to entity.
- Changed name from `lineItemSubtotalProvider` to `orderTotalsHandler`, type from `Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider` to `Oro\Bundle\OrderBundle\Handler\OrderTotalsHandler` of second argument of `Oro\Bundle\SaleBundle\Model\QuoteToOrderConverter` constructor
- Changed name from `totalProvider` to `registry`, type from `Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider` to `Doctrine\Common\Persistence\ManagerRegistry` of third argument of `Oro\Bundle\SaleBundle\Model\QuoteToOrderConverter` constructor
* The methods `addPaymentTerm`, `isOverridePaymentTermGranted`, `getAccountPaymentTermId` and `getAccountGroupPaymentTermId` was removed from class `QuoteType`.
- Removed third argument `paymentTermProvider` from  ``Oro\Bundle\SaleBundle\Form\Type\QuoteType` constructor
- Changed name from `securityFacade` to `quoteAddressSecurityProvider`, type from `Oro\Bundle\SecurityBundle\SecurityFacade` to `Oro\Bundle\SaleBundle\Provider\QuoteAddressSecurityProvider` of first argument of `Oro\Bundle\SaleBundle\Form\Type\QuoteType` constructor
- `Oro\Bundle\SaleBundle\Form\Type\QuoteType` `SecurityFacade $securityFacade` and `PaymentTermProvider $paymentTermProvider` removed, use `\Oro\Bundle\PaymentTermBundle\Form\Extension\PaymentTermAclExtension` instead


SEOBundle
---------
* New method `setBlockPriority` were added to class `BaseFormViewListener`.
- Changed name from `entitiyClass` to `entityClass`second argument of method `Oro\Bundle\SEOBundle\EventListener\BaseFormViewListener:addViewPageBlock`
- Changed name from `html` to `descriptionTemplate`second argument of method `Oro\Bundle\SEOBundle\EventListener\BaseFormViewListener:addSEOBlock`
- Added third argument `keywordsTemplate` to method `Oro\Bundle\SEOBundle\EventListener\BaseFormViewListener:addSEOBlock`

ShippingBundle
--------------
* The following classes was removed:
    - `ShippingRulesProvider`
    - `ShippingRuleDestination`
    - `ShippingRuleRepository`
    - `ShippingRuleMethodTypeConfigRepository`
    - `ShippingRuleMethodConfigRepository`
    - `ShippingRule`
    - `ShippingRuleMethodConfig`
    - `ShippingRuleMethodTypeConfig`
    - `ExtendShippingRuleMethodConfig`
    - `ExtendShippingRule`
    - `ExtendShippingRuleMethodTypeConfig`
    - `Oro\Bundle\ShippingBundle\Controller\Api\Rest\ShippingRuleController`
    - `Oro\Bundle\ShippingBundle\Controller\ShippingRuleController`
    - `ShippingRuleHandler`
    - `RuleMethodConfigSubscriber`
    - `RuleMethodTypeConfigCollectionSubscriber`
    - `RuleMethodConfigCollectionSubscriber`
    - `ShippingRuleType`
    - `ShippingRuleMethodTypeConfigType`
    - `ShippingRuleMethodConfigCollectionType`
    - `ShippingRuleMethodConfigType`
    - `ShippingRuleDestinationType`
    - `ShippingRuleMethodTypeConfigCollectionType`
    - `ShippingContextInterface`
    - `ShippingContextFactory`
    - `LineItemDecorator`
    - `LineItemDecoratorFactory`
* The `ShippingPriceProvider::__construct` method has been updated. Pass `ShippingMethodsConfigsRulesProvider` as a second argument of the method instead of `ShippingRulesProvider`.
* The `ShippingPriceProvider::getMethodTypesConfigs` method has been updated. Pass `ShippingMethodConfig` as a second argument of the method instead of `ShippingRuleMethodConfig`.
* The method `handleShippingRuleStatuses` was removed from class `StatusMassActionHandler`.
* New protected method `handleShippingMethodsConfigsRuleStatuses` were added to class `StatusMassActionHandler`.
* The following methods was removed from class `ShippingLineItem`:
    - `setPrice`
    - `setProduct`
    - `setProductHolder`
    - `setProductUnit`
    - `setQuantity`
    - `setWeight`
    - `setDimensions`
* New method `__construct` were added to class `ShippingLineItem`.
* The following methods was removed from class `ShippingContext`:
    - `setSourceEntity`
    - `setSourceEntityIdentifier`
    - `setLineItems`
    - `setBillingAddress`
    - `setShippingAddress`
    - `setShippingOrigin`
    - `setPaymentMethod`
    - `setCurrency`
    - `setSubtotal`
* New methods `__construct`, `getCustomer` and `getCustomerUser` were added to class `ShippingContext`.
- Removed method `isShippingRulesUpdated` from class `Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache`
- Changed name from `doctrine` to `cacheKeyGenerator`, type from `Symfony\Bridge\Doctrine\ManagerRegistry` to `Oro\Bundle\ShippingBundle\Context\ShippingContextCacheKeyGenerator` of second argument of `Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache` constructor
* New method `deleteAllPrices` were added to class `ShippingPriceCache`.
- Removed method `formatShippingMethodWithType` from class `Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter`

ShoppingListBundle
------------------
- Removed class `Oro\Bundle\ShoppingListBundle\EventListener\ShoppingListListener`
- Removed method `setShoppingListClass` from class `Oro\Bundle\ShoppingListBundle\Layout\DataProvider\AccountUserShoppingListsProvider`
- Changed name from `doctrineHelper` to `requestStack`, type from `Oro\Bundle\EntityBundle\ORM\DoctrineHelper` to `Symfony\Component\HttpFoundation\RequestStack` of first argument of `Oro\Bundle\ShoppingListBundle\Layout\DataProvider\AccountUserShoppingListsProvider` constructor
- Changed name from `securityFacade` to `totalManager`, type from `Oro\Bundle\SecurityBundle\SecurityFacade` to `Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager` of second argument of `Oro\Bundle\ShoppingListBundle\Layout\DataProvider\AccountUserShoppingListsProvider` constructor
- Changed name from `requestStack` to `shoppingListManager`, type from `Symfony\Component\HttpFoundation\RequestStack` to `Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager` of third argument of `Oro\Bundle\ShoppingListBundle\Layout\DataProvider\AccountUserShoppingListsProvider` constructor
- Removed method `findCurrentForAccountUser` from class `Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository`
- Removed method `findOneForAccountUser` from class `Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository`
- Removed method `createFindForAccountUserQueryBuilder` from class `Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository`
- Removed method `findAllExceptCurrentForAccountUser` from class `Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository`
- Removed method `findLatestForAccountUserExceptCurrent` from class `Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository`
- Removed method `findOneByIdWithRelations` from class `Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository`
- Changed name from `accountUser` to `aclHelper`, type from `Oro\Bundle\CustomerBundle\Entity\AccountUser` to `Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper` of first argument of method `Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository:findAvailableForAccountUser`
- Changed name from `accountUser` to `aclHelper`, type from `Oro\Bundle\CustomerBundle\Entity\AccountUser` to `Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper` of first argument of method `Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository:findByUser`
- Added third argument `excludeShoppingList` to method `Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository:findByUser`
- Changed name from `accountUser` to `aclHelper`, type from `Oro\Bundle\CustomerBundle\Entity\AccountUser` to `Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper` of first argument of method `Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository:findByUserAndId`
- Added protected method `modifyQbWithRelations` to class `Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository`
- Changed name from `securityFacade` to `tokenStorage`, type from `Oro\Bundle\SecurityBundle\SecurityFacade` to `Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage` of first argument of `Oro\Bundle\ShoppingListBundle\EventListener\FrontendProductDatagridListener` constructor
- Changed name from `manager` to `aclHelper`, type from `Doctrine\Bundle\DoctrineBundle\Registry` to `Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper` of second argument of `Oro\Bundle\ShoppingListBundle\EventListener\FrontendProductDatagridListener` constructor
- Added third argument `manager` to `Oro\Bundle\ShoppingListBundle\EventListener\FrontendProductDatagridListener` constructor
- Removed first argument `accountUser` from method `Oro\Bundle\ShoppingListBundle\EventListener\FrontendProductDatagridListener:getCurrentShoppingList`
- Added name from `id` to `shoppingList`, type `Oro\Bundle\ShoppingListBundle\Entity\ShoppingList` to first argument of method `Oro\Bundle\ShoppingListBundle\Controller\Frontend\ShoppingListController:viewAction`
- Added method `setOwnerAction` to class `Oro\Bundle\ShoppingListBundle\Controller\Frontend\Api\Rest\ShoppingListController`
- Added eighth argument `aclHelper` to `Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager` constructor
- Added ninth argument `cache` to `Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager` constructor
- Added first argument `flush` to method `Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager:create`
- Added second argument `label` to method `Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager:create`
- Added first argument `sortCriteria` to method `Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager:getShoppingLists`
- Added method `getShoppingListsWithCurrentFirst` to class `Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager`
- Removed method `getAccountUser` from class `Oro\Bundle\ShoppingListBundle\Form\Type\FrontendLineItemWidgetType`
- Changed name from `tokenStorage` to `translator`, type from `Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage` to `Symfony\Component\Translation\TranslatorInterface` of second argument of `Oro\Bundle\ShoppingListBundle\Form\Type\FrontendLineItemWidgetType` constructor
- Changed name from `translator` to `aclHelper`, type from `Symfony\Component\Translation\TranslatorInterface` to `Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper` of third argument of `Oro\Bundle\ShoppingListBundle\Form\Type\FrontendLineItemWidgetType` constructor
- Added fourth argument `shoppingListManager` to `Oro\Bundle\ShoppingListBundle\Form\Type\FrontendLineItemWidgetType` constructor
- Removed method `setOperationManager` from class `Oro\Bundle\ShoppingListBundle\Processor\QuickAddCheckoutProcessor`
- Removed method `setOperationName` from class `Oro\Bundle\ShoppingListBundle\Processor\QuickAddCheckoutProcessor`
- Added method `setActionGroupRegistry` to class `Oro\Bundle\ShoppingListBundle\Processor\QuickAddCheckoutProcessor`
- Added method `setActionGroupName` to class `Oro\Bundle\ShoppingListBundle\Processor\QuickAddCheckoutProcessor`
- Added method `isAllowed` to class `Oro\Bundle\ShoppingListBundle\Processor\QuickAddCheckoutProcessor`
- Added protected method `getActionGroup` to class `Oro\Bundle\ShoppingListBundle\Processor\QuickAddCheckoutProcessor`

TaxBundle
---------
* The classes `DigitalProductEventListener` and `AbstractUnitRowResolver` was removed.
* New methods `getShippingTaxCodes` and `isShippingRatesIncludeTax` were added to class `TaxationSettingsProvider`.
* New methods `getShippingCost` and `setShippingCost` were added to class `Taxable`.
* New methods `__construct` and `getTaxCodes` were added to class `ShippingResolver`.
* New protected method `calculateAdjustment` were added to class `UnitResolver`.
* New protected method `calculateAdjustment` were added to class `ShippingResolver`.
* New protected method `mergeShippingData` were added to class `TotalResolver`.
* New protected method `calculateAdjustment` were added to class `RowTotalResolver`.

UPSBundle
---------
* The `UPSTransport::__construct` method has been updated. Pass `Psr\Log\LoggerInterface` as a first argument of the method instead of `Doctrine\Common\Persistence\ManagerRegistry`.
- Added first argument `transportId` to method `Oro\Bundle\UPSBundle\Cache\ShippingPriceCache:deleteAll`
- Added protected method `setNamespace` to class `Oro\Bundle\UPSBundle\Cache\ShippingPriceCache`
- Changed name from `shippingPriceCache` to `upsShippingPriceCache`second argument of `Oro\Bundle\UPSBundle\Handler\InvalidateCacheAtHandler` constructor
- Added third argument `shippingPriceCache` to `Oro\Bundle\UPSBundle\Handler\InvalidateCacheAtHandler` constructor
- Added fourth argument `deferredScheduler` to `Oro\Bundle\UPSBundle\Handler\InvalidateCacheAtHandler` constructor
- Added protected method `convertDatetimeToCron` to class `Oro\Bundle\UPSBundle\Handler\InvalidateCacheAtHandler`

VisibilityBundle
----------------
- Changed type from `Oro\Bundle\CatalogBundle\Model\CategoryMessageHandler` to `Oro\Bundle\VisibilityBundle\Model\CategoryMessageHandler` of first argument of `Oro\Bundle\VisibilityBundle\Entity\EntityListener\CategoryListener` constructor
- Changed type from `Oro\Bundle\ProductBundle\Model\ProductMessageHandler` to `Oro\Bundle\VisibilityBundle\Model\ProductMessageHandler` of first argument of `Oro\Bundle\VisibilityBundle\EventListener\CategoryListener` constructor
- Changed type from `Oro\Bundle\CatalogBundle\Model\CategoryMessageFactory` to `Oro\Bundle\VisibilityBundle\Model\CategoryMessageFactory` of fourth argument of `Oro\Bundle\VisibilityBundle\Async\Visibility\CategoryProcessor` constructor
- Changed type from `Oro\Bundle\ProductBundle\Model\ProductMessageFactory` to `Oro\Bundle\VisibilityBundle\Model\ProductMessageFactory` of second argument of `Oro\Bundle\VisibilityBundle\Async\Visibility\ProductProcessor` constructor
- Changed type from `Oro\Bundle\ProductBundle\Model\ProductMessageHandler` to `Oro\Bundle\VisibilityBundle\Model\ProductMessageHandler` of sixth argument of `Oro\Bundle\VisibilityBundle\Async\Visibility\CategoryVisibilityProcessor` constructor

WebsiteBundle
-------------------------
* New methods `__construct` were added to class `WebsiteEntityListener`.
- Removed second argument `event` from method `Oro\Bundle\WebsiteBundle\Entity\EntityListener\WebsiteEntityListener:prePersist`

WebsiteSearchBundle
-------------------
- Changed name from `entities` to `entityClass`first argument of `Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent` constructor
- Changed name from `context` to `entities`second argument of `Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent` constructor
- Added third argument `context` to `Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent` constructor
* New method `getEntityClass` were added to class `IndexEntityEvent`.
- Added fourth argument `addToAllText` to method `Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent:addField`
- Added fifth argument `addToAllText` to method `Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent:addPlaceholderField`
