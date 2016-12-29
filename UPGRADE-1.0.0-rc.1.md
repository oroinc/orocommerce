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
* The `DefaultShippingMethodSetter:__construct` method has been updated. Pass `CheckoutShippingContextFactory` as a first argument of the method instead of `ShippingContextProviderFactory`.
* The `CheckoutShippingContextProvider:__construct` method has been updated. Pass `CheckoutShippingContextFactory` as a first argument of the method instead of `ShippingContextProviderFactory`.
* The `CheckoutRepository:getCheckoutByQuote` method has been updated. Pass `\Oro\Bundle\CustomerBundle\Entity\AccountUser` as a second argument of the method.
* New method `findCheckoutByAccountUserAndSourceCriteria` were added to class `CheckoutRepository`.
* The `ResolvePaymentTermListener:__construct` method has been updated. Pass `Doctrine\Common\Persistence\ManagerRegistry` as a second argument of the method instead of `Symfony\Component\EventDispatcher\EventDispatcherInterface`. Pass `Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider` as a third argument of the method.
* The `ResolvePaymentTermListener:onResolvePaymentTerm` method has been updated. Pass `Oro\Bundle\PaymentTermBundle\Event\ResolvePaymentTermEvent` as a first argument of the method instead of `Oro\Bundle\PaymentBundle\Event\ResolvePaymentTermEvent`.
* The method `getCheckout` was removed from class `CheckoutController`.
* New protected methods `isCheckoutRestartRequired` and `restartCheckout` were added to class `CheckoutController`.
* The `OrderMapper:__construct` method has been updated. Pass `Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider` as a third argument of the method.
* New protected method `assignPaymentTerm` were added to class `OrderMapper`.
* The `ShippingMethodEnabledMapper:__construct` method has been updated. Pass `CheckoutShippingContextFactory` as a second argument of the method instead of `ShippingContextProviderFactory`.
* The `ShippingMethodDiffMapper:__construct` method has been updated. Pass `CheckoutShippingContextFactory` as a second argument of the method instead of `ShippingContextProviderFactory`.
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
* The classes `AccountMessageFactory` and `AccountUserRoleController` was removed.
* The `AccountUserController:getRolesAction` method has been updated. Pass `Symfony\Component\HttpFoundation\Request` as a first argument of the method.

- Removed method `getNoOwnershipMetadata` from class `Oro\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider`
- Removed method `getSystemLevelClass` from class `Oro\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider`
- Added protected method `createNoOwnershipMetadata` to class `Oro\Bundle\CustomerBundle\Owner\Metadata\FrontendOwnershipMetadataProvider`
- Added method `getAccountUserSelectFormView` to class `Oro\Bundle\CustomerBundle\Layout\DataProvider\FrontendAccountUserFormProvider`
- Added method `isGrantedViewDeep` to class `Oro\Bundle\CustomerBundle\Security\AccountUserProvider`
- Added method `isGrantedViewSystem` to class `Oro\Bundle\CustomerBundle\Security\AccountUserProvider`
- Removed name from `aclHelper` to `accountId`, type `Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper` of first argument of method `Oro\Bundle\CustomerBundle\Entity\Repository\AccountRepository:getChildrenIds`
- Added name from `accountId` to `aclHelper`, type `Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper` to second argument of method `Oro\Bundle\CustomerBundle\Entity\Repository\AccountRepository:getChildrenIds`
- Added method `getCustomer` to class `Oro\Bundle\CustomerBundle\Entity\AccountUser`
- Added method `setCustomer` to class `Oro\Bundle\CustomerBundle\Entity\AccountUser`
- Added name from `actionCallback` to `repository`, type `Oro\Bundle\CustomerBundle\Entity\Repository\AccountRepository` to second argument of `Oro\Bundle\CustomerBundle\EventListener\Datagrid\AccountDatagridListener` constructor
- Added third argument `actionCallback` to `Oro\Bundle\CustomerBundle\EventListener\Datagrid\AccountDatagridListener` constructor
- Added second argument `withChildCustomers` to method `Oro\Bundle\CustomerBundle\EventListener\Datagrid\AccountDatagridListener:showAllAccountItems`
- Added protected method `permissionShowAllAccountItemsForChild` to class `Oro\Bundle\CustomerBundle\EventListener\Datagrid\AccountDatagridListener`
- Removed method `onBuildBefore` from class `Oro\Bundle\CustomerBundle\EventListener\Datagrid\AccountUserRoleDatagridListener`
- Changed name from `securityFacade` to `aclHelper`, type from `Oro\Bundle\SecurityBundle\SecurityFacade` to `Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper` of first argument of `Oro\Bundle\CustomerBundle\EventListener\Datagrid\AccountUserRoleDatagridListener` constructor
- Added method `onBuildAfter` to class `Oro\Bundle\CustomerBundle\EventListener\Datagrid\AccountUserRoleDatagridListener`
- Added second argument `permission` to method `Oro\Bundle\CustomerBundle\Controller\AclPermissionController:aclAccessLevelsAction`
- Added method `preSetData` to class `Oro\Bundle\CustomerBundle\Form\Type\FrontendAccountUserRoleType`
- Added third argument `aclHelper` to `Oro\Bundle\CustomerBundle\Form\Type\FrontendAccountUserRoleSelectType` constructor
- Added method `preSetData` to class `Oro\Bundle\CustomerBundle\Form\Type\FrontendAccountUserProfileType`
- Removed method `isGrantedAccountUserRole` from class `Oro\Bundle\CustomerBundle\Acl\Voter\AccountUserRoleVoter`
- Added method `__construct` to class `Oro\Bundle\CustomerBundle\Acl\Voter\AccountVoter`
- `Oro\Bundle\CustomerBundle\Entity\AccountGroup` made extendable

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

- Added name from `code` to `request`, type `Symfony\Component\HttpFoundation\Request` to first argument of method `Oro\Bundle\FrontendBundle\Controller\FrontendController:exceptionAction`
- Changed name from `text` to `code`second argument of method `Oro\Bundle\FrontendBundle\Controller\FrontendController:exceptionAction`
- Added third argument `text` to method `Oro\Bundle\FrontendBundle\Controller\FrontendController:exceptionAction`
- Added method `isFrontendUrl` to class `Oro\Bundle\FrontendBundle\Request\FrontendHelper`
- `oro_frontend.listener.datagrid.fields` and `oro_frontend.listener.enum_filter_frontend_listener` priority fixed to make them executed first

InventoryBundle
---------------
- Removed method `__construct` from class `Oro\Bundle\InventoryBundle\EventListener\CategoryManageInventoryFormViewListener`
- Removed method `__construct` from class `Oro\Bundle\InventoryBundle\EventListener\ProductManageInventoryFormViewListener`
- Removed method `getProductFromRequest` from class `Oro\Bundle\InventoryBundle\EventListener\ProductManageInventoryFormViewListener`

MenuBundle
----------
* The `MenuBundle` bundle was removed. All logic replaced to [`CommerceMenuBundle`](#commercemenubundle) bundle.

MoneyOrderBundle
----------------
* The methods `__construct` and `isEnabled` was removed from class `MoneyOrder`.
* The `MoneyOrder:isApplicable` method has been updated. Pass `PaymentContextInterface` as a first argument of the method.
* The `MoneyOrder:getOptions` method has been updated. Pass `PaymentContextInterface` as a first argument of the method.
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
- Removed class `Oro\Bundle\OrderBundle\Layout\DataProvider\OrderShippingMethodProvider`
- Removed class `Oro\Bundle\OrderBundle\Formatter\ShippingMethodFormatter`
- Removed class `Oro\Bundle\OrderBundle\Controller\Frontend\AjaxOrderController`
- Removed class `Oro\Bundle\OrderBundle\Controller\Frontend\Api\Rest\OrderController`
- Removed class `Oro\Bundle\OrderBundle\Form\Type\FrontendOrderType`
- Removed class `Oro\Bundle\OrderBundle\Form\Type\FrontendOrderLineItemType`
- Changed name from `shippingContextFactory` to `shippingLineItemConverter`, type from `Oro\Bundle\ShippingBundle\Factory\ShippingContextFactory` to `Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface` of second argument of `Oro\Bundle\OrderBundle\Factory\OrderShippingContextFactory` constructor
- Added third argument `shippingContextBuilderFactory` to `Oro\Bundle\OrderBundle\Factory\OrderShippingContextFactory` constructor
- Removed third argument `shippingMethodFormatter` from  ``Oro\Bundle\OrderBundle\Twig\OrderExtension` constructor
- Added third argument `rateConverter` to `Oro\Bundle\OrderBundle\Handler\OrderTotalsHandler` constructor
- Changed name from `localeSettings` to `currencyProvider`, type from `Oro\Bundle\LocaleBundle\Model\LocaleSettings` to `Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface` of first argument of `Oro\Bundle\OrderBundle\Handler\OrderCurrencyHandler` constructor
- Removed method `setPaymentTerm` from class `Oro\Bundle\OrderBundle\Entity\Order`
- Removed method `getPaymentTerm` from class `Oro\Bundle\OrderBundle\Entity\Order`. Use `oro_payment_term.provider.payment_term_association` to assign PaymentTerm to entity
- Added method `loadMultiCurrencyFields` to class `Oro\Bundle\OrderBundle\Entity\Order`
- Added method `updateMultiCurrencyFields` to class `Oro\Bundle\OrderBundle\Entity\Order`
- Added method `getBaseSubtotalValue` to class `Oro\Bundle\OrderBundle\Entity\Order`
- Added method `setBaseSubtotalValue` to class `Oro\Bundle\OrderBundle\Entity\Order`
- Changed name from `subtotal` to `value`first argument of method `Oro\Bundle\OrderBundle\Entity\Order:setSubtotal`
- Added method `setSubtotalObject` to class `Oro\Bundle\OrderBundle\Entity\Order`
- Added method `getSubtotalObject` to class `Oro\Bundle\OrderBundle\Entity\Order`
- Added method `getBaseTotalValue` to class `Oro\Bundle\OrderBundle\Entity\Order`
- Added method `setBaseTotalValue` to class `Oro\Bundle\OrderBundle\Entity\Order`
- Changed name from `total` to `value`first argument of method `Oro\Bundle\OrderBundle\Entity\Order:setTotal`
- Added method `getTotalObject` to class `Oro\Bundle\OrderBundle\Entity\Order`
- Added method `setTotalObject` to class `Oro\Bundle\OrderBundle\Entity\Order`
- Added protected method `fixCurrencyInMultiCurrencyFields` to class `Oro\Bundle\OrderBundle\Entity\Order`
- Added protected method `setSubtotalCurrency` to class `Oro\Bundle\OrderBundle\Entity\Order`
- Added protected method `setTotalCurrency` to class `Oro\Bundle\OrderBundle\Entity\Order`
- Added protected method `updateSubtotal` to class `Oro\Bundle\OrderBundle\Entity\Order`
- Added protected method `updateTotal` to class `Oro\Bundle\OrderBundle\Entity\Order`
- Changed name from `applicationsHelper` to `applicationProvider`, type from `Oro\Bundle\ActionBundle\Helper\ApplicationsHelper` to `Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface` of second argument of `Oro\Bundle\OrderBundle\EventListener\TotalCalculateListener` constructor
- Changed type from `Oro\Bundle\PaymentBundle\Provider\PaymentTermProvider` to `Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider` of first argument of `Oro\Bundle\OrderBundle\EventListener\Order\OrderPaymentTermEventListener` constructor
- Changed type from `Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider` to `Oro\Bundle\OrderBundle\Provider\TotalProvider` of first argument of `Oro\Bundle\OrderBundle\EventListener\Order\OrderTotalEventListener` constructor
- Removed method `infoAction` from class `Oro\Bundle\OrderBundle\Controller\Frontend\OrderController`
- Changed type from `Oro\Bundle\PaymentBundle\Provider\PaymentTermProvider` to `Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider` of fourth argument of `Oro\Bundle\OrderBundle\RequestHandler\FrontendOrderDataHandler` constructor
- Removed method `isOverridePaymentTermGranted` from class `Oro\Bundle\OrderBundle\Form\Type\OrderType`
- Removed method `getAccountPaymentTermId` from class `Oro\Bundle\OrderBundle\Form\Type\OrderType`
- Removed method `getAccountGroupPaymentTermId` from class `Oro\Bundle\OrderBundle\Form\Type\OrderType`
- Removed method `addPaymentTerm` from class `Oro\Bundle\OrderBundle\Form\Type\OrderType`
- Changed name from `securityFacade` to `orderAddressSecurityProvider`, type from `Oro\Bundle\SecurityBundle\SecurityFacade` to `Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider` of first argument of `Oro\Bundle\OrderBundle\Form\Type\OrderType` constructor
- Changed name from `orderAddressSecurityProvider` to `orderCurrencyHandler`, type from `Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider` to `Oro\Bundle\OrderBundle\Handler\OrderCurrencyHandler` of second argument of `Oro\Bundle\OrderBundle\Form\Type\OrderType` constructor
- Changed name from `paymentTermProvider` to `subtotalSubscriber`, type from `Oro\Bundle\PaymentBundle\Provider\PaymentTermProvider` to `Oro\Bundle\OrderBundle\Form\Type\EventListener\SubtotalSubscriber` of third argument of `Oro\Bundle\OrderBundle\Form\Type\OrderType` constructor
- Added fourth argument `rateConverter` to `Oro\Bundle\OrderBundle\Total\TotalHelper` constructor

- `Oro\Bundle\OrderBundle\Form\Type\OrderType` `SecurityFacade $securityFacade` and `PaymentTermProvider $paymentTermProvider` removed, use `\Oro\Bundle\PaymentTermBundle\Form\Extension\PaymentTermAclExtension` instead

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
* The `PaymentMethodInterface:isApplicable` method has been updated. Pass `PaymentContextInterface` as a first argument of the method.
* The method `getOrder` was removed from interface `PaymentMethodViewInterface`.
* The `PaymentMethodViewInterface:getOptions` method has been updated. Pass `PaymentContextInterface` as a first argument of the method.
* The method `getName` was removed from class `PaymentMethodExtension`.
* New method `getPaymentMethodConfigRenderData` were added to class `PaymentMethodExtension`.
* The `PaymentMethodExtension:__construct` method has been updated. Pass `Symfony\Component\EventDispatcher\EventDispatcherInterface` as a third argument of the method.
* The `PaymentMethodApplicable:__construct` method has been updated. Pass `PaymentMethodProvider` as a first argument of the method instead of `PaymentMethodRegistry`. Second argument of the method was removed.
* The `HasApplicablePaymentMethods:__construct` method has been updated. Pass `PaymentMethodProvider` as a first argument of the method instead of `PaymentMethodRegistry`. Second argument of the method was removed.
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
* The `PayflowGatewayView:getOptions` method has been updated. Pass `PaymentContextInterface` as a first argument of the method.
* The method `getOrder` was removed from class `PayflowExpressCheckoutView`.
* The `PayflowExpressCheckoutView:getOptions` method has been updated. Pass `PaymentContextInterface` as a first argument of the method.
* The method `isEnabled` was removed from class `PayflowGateway`.
* The `PayflowGateway:isApplicable` method has been updated. Pass `PaymentContextInterface` as a first argument of the method.
* The method `isEnabled` was removed from class `PayflowExpressCheckout`.
* The `PayflowExpressCheckout:isApplicable` method has been updated. Pass `PaymentContextInterface` as a first argument of the method.
- Added method `configureOptions` to class `Oro\Bundle\PayPalBundle\Form\Type\CurrencySelectionType`

PricingBundle
-------------
- Removed class `Oro\Bundle\PricingBundle\Validator\Constraints\DefaultCurrency`
- Removed class `Oro\Bundle\PricingBundle\Validator\Constraints\DefaultCurrencyValidator`
- Removed class `Oro\Bundle\PricingBundle\Form\Extension\SystemCurrencyFormExtension`
- Removed class `Oro\Bundle\PricingBundle\Form\Type\DefaultCurrencySelectionType`
- Added seventh argument `triggerHandler` to `Oro\Bundle\PricingBundle\Builder\AbstractCombinedPriceListBuilder` constructor
- Added third argument `triggerHandler` to `Oro\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector` constructor
- Added method `getId` to class `Oro\Bundle\PricingBundle\Entity\BaseCombinedPriceListRelation`
- Removed method `deleteUnusedPriceLists` from class `Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository`
- Removed method `getUnusedPriceListsIterator` from class `Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository`
- Added method `deletePriceLists` to class `Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository`
- Added method `getUnusedPriceListsIds` to class `Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository`
- Added method `hasOtherRelations` to class `Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository`
- Added method `getProductIdsByPriceLists` to class `Oro\Bundle\PricingBundle\Entity\Repository\BaseProductPriceRepository`
- Added method `findMinByWebsiteForFilter` to class `Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository`
- Added method `findMinByWebsiteForSort` to class `Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository`
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

- Removed class `Oro\Bundle\ProductBundle\Model\ProductMessageHandler`
- Removed class `Oro\Bundle\ProductBundle\Model\ProductMessageFactory`
- Removed class `Oro\Bundle\ProductBundle\Form\Type\ProductCustomFieldsChoiceType`
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
- Added method `getTypes` to class `Oro\Bundle\ProductBundle\Entity\Product`
- Added method `isSimple` to class `Oro\Bundle\ProductBundle\Entity\Product`
- Added method `isConfigurable` to class `Oro\Bundle\ProductBundle\Entity\Product`
- Added method `getParentVariantLinks` to class `Oro\Bundle\ProductBundle\Entity\Product`
- Added method `addParentVariantLink` to class `Oro\Bundle\ProductBundle\Entity\Product`
- Added method `removeParentVariantLink` to class `Oro\Bundle\ProductBundle\Entity\Product`
- Added method `getType` to class `Oro\Bundle\ProductBundle\Entity\Product`
- Added method `setType` to class `Oro\Bundle\ProductBundle\Entity\Product`
- Added method `setAttributeFamily` to class `Oro\Bundle\ProductBundle\Entity\Product`
- Added method `getAttributeFamily` to class `Oro\Bundle\ProductBundle\Entity\Product`
- Added method `getSlugPrototypes` to class `Oro\Bundle\ProductBundle\Entity\Product`
- Added method `addSlugPrototype` to class `Oro\Bundle\ProductBundle\Entity\Product`
- Added method `removeSlugPrototype` to class `Oro\Bundle\ProductBundle\Entity\Product`
- Added method `hasSlugPrototype` to class `Oro\Bundle\ProductBundle\Entity\Product`
- Added method `getSlugs` to class `Oro\Bundle\ProductBundle\Entity\Product`
- Added method `addSlug` to class `Oro\Bundle\ProductBundle\Entity\Product`
- Added method `removeSlug` to class `Oro\Bundle\ProductBundle\Entity\Product`
- Added method `resetSlugs` to class `Oro\Bundle\ProductBundle\Entity\Product`
- Added method `hasSlug` to class `Oro\Bundle\ProductBundle\Entity\Product`
- Added method `__construct` to class `Oro\Bundle\ProductBundle\EventListener\ProductHandlerListener`
- Added protected method `clearCustomExtendVariantFields` to class `Oro\Bundle\ProductBundle\EventListener\ProductHandlerListener`
- Removed method `onBuildBefore` from class `Oro\Bundle\ProductBundle\EventListener\ProductVariantCustomFieldsDatagridListener`
- Removed method `onResultAfter` from class `Oro\Bundle\ProductBundle\EventListener\ProductVariantCustomFieldsDatagridListener`
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
- Removed class `Oro\Bundle\RedirectBundle\EventListener\ForwardListener`
- Added method `build` to class `Oro\Bundle\RedirectBundle\OroRedirectBundle`
- Added method `__construct` to class `Oro\Bundle\RedirectBundle\Entity\Slug`
- Added method `getScopes` to class `Oro\Bundle\RedirectBundle\Entity\Slug`
- Added method `addScope` to class `Oro\Bundle\RedirectBundle\Entity\Slug`
- Added method `removeScope` to class `Oro\Bundle\RedirectBundle\Entity\Slug`
- Added method `resetScopes` to class `Oro\Bundle\RedirectBundle\Entity\Slug`
- Added method `getRedirects` to class `Oro\Bundle\RedirectBundle\Entity\Slug`
- Added method `addRedirect` to class `Oro\Bundle\RedirectBundle\Entity\Slug`
- Added method `removeRedirect` to class `Oro\Bundle\RedirectBundle\Entity\Slug`
- Added method `getLocalization` to class `Oro\Bundle\RedirectBundle\Entity\Slug`
- Added method `setLocalization` to class `Oro\Bundle\RedirectBundle\Entity\Slug`

SaleBundle
----------
* New methods `getAccountUser`/`setAccountUser` and `getAccount`/`setAccount` were added to class `QuoteDemand`.
- Added method `hasRecordsWithRemovingCurrencies` to class `Oro\Bundle\SaleBundle\Entity\Repository\QuoteRepository`
- Removed method `setPaymentTerm` from class `Oro\Bundle\SaleBundle\Entity\Quote`
- Removed method `getPaymentTerm` from class `Oro\Bundle\SaleBundle\Entity\Quote`. Use `oro_payment_term.provider.payment_term_association` to assign PaymentTerm to entity
- Removed method `fillSubtotals` from class `Oro\Bundle\SaleBundle\Model\QuoteToOrderConverter`
- Changed name from `lineItemSubtotalProvider` to `orderTotalsHandler`, type from `Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider` to `Oro\Bundle\OrderBundle\Handler\OrderTotalsHandler` of second argument of `Oro\Bundle\SaleBundle\Model\QuoteToOrderConverter` constructor
- Changed name from `totalProvider` to `registry`, type from `Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider` to `Doctrine\Common\Persistence\ManagerRegistry` of third argument of `Oro\Bundle\SaleBundle\Model\QuoteToOrderConverter` constructor
- Removed method `addPaymentTerm` from class `Oro\Bundle\SaleBundle\Form\Type\QuoteType`
- Removed method `isOverridePaymentTermGranted` from class `Oro\Bundle\SaleBundle\Form\Type\QuoteType`
- Removed method `getAccountPaymentTermId` from class `Oro\Bundle\SaleBundle\Form\Type\QuoteType`
- Removed method `getAccountGroupPaymentTermId` from class `Oro\Bundle\SaleBundle\Form\Type\QuoteType`
- Removed third argument `paymentTermProvider` from  ``Oro\Bundle\SaleBundle\Form\Type\QuoteType` constructor
- Changed name from `securityFacade` to `quoteAddressSecurityProvider`, type from `Oro\Bundle\SecurityBundle\SecurityFacade` to `Oro\Bundle\SaleBundle\Provider\QuoteAddressSecurityProvider` of first argument of `Oro\Bundle\SaleBundle\Form\Type\QuoteType` constructor
- `Oro\Bundle\SaleBundle\Form\Type\QuoteType` `SecurityFacade $securityFacade` and `PaymentTermProvider $paymentTermProvider` removed, use `\Oro\Bundle\PaymentTermBundle\Form\Extension\PaymentTermAclExtension` instead


SEOBundle
---------
- Added method `setBlockPriority` to class `Oro\Bundle\SEOBundle\EventListener\BaseFormViewListener`
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
* The `ShippingPriceProvider:__construct` method has been updated. Pass `ShippingMethodsConfigsRulesProvider` as a second argument of the method instead of `ShippingRulesProvider`.
* The `ShippingPriceProvider:getMethodTypesConfigs` method has been updated. Pass `ShippingMethodConfig` as a second argument of the method instead of `ShippingRuleMethodConfig`.
* The method `handleShippingRuleStatuses` was removed from class `StatusMassActionHandler`.
* New protected method `handleShippingMethodsConfigsRuleStatuses` were added to class `StatusMassActionHandler`.
- Removed class `Oro\Bundle\ShippingBundle\Context\ShippingContextInterface`
- Removed class `Oro\Bundle\ShippingBundle\Factory\ShippingContextFactory`
- Removed class `Oro\Bundle\ShippingBundle\ExpressionLanguage\LineItemDecorator`
- Removed class `Oro\Bundle\ShippingBundle\ExpressionLanguage\LineItemDecoratorFactory`
- Removed method `setPrice` from class `Oro\Bundle\ShippingBundle\Context\ShippingLineItem`
- Removed method `setProduct` from class `Oro\Bundle\ShippingBundle\Context\ShippingLineItem`
- Removed method `setProductHolder` from class `Oro\Bundle\ShippingBundle\Context\ShippingLineItem`
- Removed method `setProductUnit` from class `Oro\Bundle\ShippingBundle\Context\ShippingLineItem`
- Removed method `setQuantity` from class `Oro\Bundle\ShippingBundle\Context\ShippingLineItem`
- Removed method `setWeight` from class `Oro\Bundle\ShippingBundle\Context\ShippingLineItem`
- Removed method `setDimensions` from class `Oro\Bundle\ShippingBundle\Context\ShippingLineItem`
- Added method `__construct` to class `Oro\Bundle\ShippingBundle\Context\ShippingLineItem`
- Removed method `setSourceEntity` from class `Oro\Bundle\ShippingBundle\Context\ShippingContext`
- Removed method `setSourceEntityIdentifier` from class `Oro\Bundle\ShippingBundle\Context\ShippingContext`
- Removed method `setLineItems` from class `Oro\Bundle\ShippingBundle\Context\ShippingContext`
- Removed method `setBillingAddress` from class `Oro\Bundle\ShippingBundle\Context\ShippingContext`
- Removed method `setShippingAddress` from class `Oro\Bundle\ShippingBundle\Context\ShippingContext`
- Removed method `setShippingOrigin` from class `Oro\Bundle\ShippingBundle\Context\ShippingContext`
- Removed method `setPaymentMethod` from class `Oro\Bundle\ShippingBundle\Context\ShippingContext`
- Removed method `setCurrency` from class `Oro\Bundle\ShippingBundle\Context\ShippingContext`
- Removed method `setSubtotal` from class `Oro\Bundle\ShippingBundle\Context\ShippingContext`
- Added method `__construct` to class `Oro\Bundle\ShippingBundle\Context\ShippingContext`
- Added method `getCustomer` to class `Oro\Bundle\ShippingBundle\Context\ShippingContext`
- Added method `getCustomerUser` to class `Oro\Bundle\ShippingBundle\Context\ShippingContext`
- Removed method `isShippingRulesUpdated` from class `Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache`
- Changed name from `doctrine` to `cacheKeyGenerator`, type from `Symfony\Bridge\Doctrine\ManagerRegistry` to `Oro\Bundle\ShippingBundle\Context\ShippingContextCacheKeyGenerator` of second argument of `Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache` constructor
- Added method `deleteAllPrices` to class `Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache`
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
- Removed class `Oro\Bundle\TaxBundle\EventListener\Config\DigitalProductEventListener`
- Removed class `Oro\Bundle\TaxBundle\Resolver\AbstractUnitRowResolver`
- Added method `getShippingTaxCodes` to class `Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider`
- Added method `isShippingRatesIncludeTax` to class `Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider`
- Added method `getShippingCost` to class `Oro\Bundle\TaxBundle\Model\Taxable`
- Added method `setShippingCost` to class `Oro\Bundle\TaxBundle\Model\Taxable`
- Added protected method `calculateAdjustment` to class `Oro\Bundle\TaxBundle\Resolver\UnitResolver`
- Added method `__construct` to class `Oro\Bundle\TaxBundle\Resolver\ShippingResolver`
- Added method `getTaxCodes` to class `Oro\Bundle\TaxBundle\Resolver\ShippingResolver`
- Added protected method `calculateAdjustment` to class `Oro\Bundle\TaxBundle\Resolver\ShippingResolver`
- Added protected method `mergeShippingData` to class `Oro\Bundle\TaxBundle\Resolver\TotalResolver`
- Added protected method `calculateAdjustment` to class `Oro\Bundle\TaxBundle\Resolver\RowTotalResolver`

UPSBundle
---------
* The `UPSTransport:__construct` method has been updated. Pass `Psr\Log\LoggerInterface` as a first argument of the method instead of `Doctrine\Common\Persistence\ManagerRegistry`.
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
- Added method `__construct` to class `Oro\Bundle\WebsiteBundle\Entity\EntityListener\WebsiteEntityListener`
- Removed second argument `event` from method `Oro\Bundle\WebsiteBundle\Entity\EntityListener\WebsiteEntityListener:prePersist`

WebsiteSearchBundle
-------------------
- Changed name from `entities` to `entityClass`first argument of `Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent` constructor
- Changed name from `context` to `entities`second argument of `Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent` constructor
- Added third argument `context` to `Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent` constructor
- Added method `getEntityClass` to class `Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent`
- Added fourth argument `addToAllText` to method `Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent:addField`
- Added fifth argument `addToAllText` to method `Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent:addPlaceholderField`
