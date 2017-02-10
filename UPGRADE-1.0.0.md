UPGRADE FROM 1.0.0-RC.1 to 1.0.0
=======================================

General
-------
* For upgrade from **1.0.0-rc.1** use command:
```bash
php app/console oro:platform:upgrade20 --env=prod --force
```

CatalogBundle
-------------
* New method `isUpdatedAtSet` was added to `Category` entity.
* New method `__construct` was added to `CategoryUnitPrecisionType` form type. Pass `CategoryDefaultProductUnitOptionsVisibilityInterface` as a first argument of the method.
* Methods `FeaturedCategoriesProvider::getAll` and `FeaturedCategoriesProvider::setCategories` were updated. Pass `array` as a first argument of the method.

CheckoutBundle
--------------
* The class `CheckoutGridAccountUserNameListener` was removed.
* The method `DefaultShippingMethodSetter::__construct` has been updated. Pass `CheckoutShippingMethodsProviderInterface` as a first argument of the method instead of `CheckoutShippingContextFactory`. Second argument was removed.
* New method `__construct` was added to `CategoryUnitPrecisionType` form type. Pass `CategoryDefaultProductUnitOptionsVisibilityInterface` as a first argument of the method.
* The method `CheckoutRepository::findCheckoutByAccountUserAndSourceCriteria` was renamed to `CheckoutRepository::findCheckoutByCustomerUserAndSourceCriteria`.
* The following methods were renamed in `Checkout` entity:
    - `getAccountUser`/`setAccountUser` to `getCustomerUser`/`setCustomerUser`
    - `getAccount`/`setAccount` to `getCustomer`/`setCustomer`
* The method `ShippingMethodEnabledMapper::__construct` has been updated. Pass `CheckoutShippingMethodsProviderInterface` as a first argument of the method instead of `\Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider`. Second argument was removed.
* The method `ShippingMethodDiffMapper::__construct` has been updated. Pass `CheckoutShippingMethodsProviderInterface` as a first argument of the method instead of `\Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider`. Second argument was removed.
* The protected method `CheckoutAddressType::initAccountAddressField` was renamed to `CheckoutAddressType::initCustomerAddressField`.

CustomerBundle
--------------
* The following classes were renamed:
    - `AccountExtension` to `CustomerExtension`
    - `FrontendAccountUserFormProvider` to `FrontendCustomerUserFormProvider`
    - `FrontendAccountUserRegistrationFormProvider` to `FrontendCustomerUserRegistrationFormProvider`
    - `FrontendAccountAddressFormProvider` to `FrontendCustomerAddressFormProvider`
    - `FrontendAccountUserRoleFormProvider` to `FrontendCustomerUserRoleFormProvider`
    - `FrontendAccountUserRoleOptionsProvider` to `FrontendCustomerUserRoleOptionsProvider`
    - `FrontendAccountUserAddressFormProvider` to `FrontendCustomerUserAddressFormProvider`
    - `AccountUserMenuBuilder` to `CustomerUserMenuBuilder`
    - `AccountUserProvider` to `CustomerUserProvider`
    - `AccountTreeHandler` to `CustomerTreeHandler`
    - `ScopeAccountCriteriaProvider` to `ScopeCustomerCriteriaProvider`
    - `AccountUserRelationsProvider` to `CustomerUserRelationsProvider`
    - `ScopeAccountGroupCriteriaProvider` to `ScopeCustomerGroupCriteriaProvider`
    - `AccountUserProcessor` to `CustomerUserProcessor`
    - `AccountEvent` to `CustomerEvent`
    - `AccountGroupEvent` to `CustomerGroupEvent`
    - `AccountAwareInterface` to `CustomerAwareInterface`
    - `Account` to `Customer`
    - `AccountUserAddress` to `CustomerUserAddress`
    - `AccountRepository` to `CustomerRepository`
    - `AccountAddressRepository` to `CustomerAddressRepository`
    - `AccountGroupRepository` to `CustomerGroupRepository`
    - `AccountUserAddressRepository` to `CustomerUserAddressRepository`
    - `AccountAddress` to `CustomerAddress`
    - `AccountGroupAwareInterface` to `CustomerGroupAwareInterface`
    - `AccountOwnerAwareInterface` to `CustomerOwnerAwareInterface`
    - `AccountGroup` to `CustomerGroup`
    - `AccountUser` to `CustomerUser`
    - `AccountUserSettings` to `CustomerUserSettings`
    - `AccountUserManager` to `CustomerUserManager`
    - `AccountUserRole` to `CustomerUserRole`
    - `AccountRolePageListener` to `CustomerRolePageListener`
    - `AccountDatagridListener` to `CustomerDatagridListener`
    - `AccountUserDatagridListener` to `CustomerUserDatagridListener`
    - `AccountUserRoleDatagridListener` to `CustomerUserRoleDatagridListener`
    - `AccountUserByAccountExtension` to `CustomerUserByCustomerExtension`
    - `AccountUserExtension` to `CustomerUserExtension`
    - `AccountActionPermissionProvider` to `CustomerActionPermissionProvider`
    - `ExtendAccount` to `ExtendCustomer`
    - `ExtendAccountGroup` to `ExtendCustomerGroup`
    - `ExtendAccountUserAddress` to `ExtendCustomerUserAddress`
    - `ExtendAccountUserSettings` to `ExtendCustomerUserSettings`
    - `ExtendAccountAddress` to `ExtendCustomerAddress`
    - `AccountGroupController` to `CustomerGroupController`
    - `AccountUserAddressController` to `CustomerUserAddressController`
    - `AccountController` to `CustomerController`
    - `AccountAddressController` to `CustomerAddressController`
    - `AccountUserRegisterController` to `CustomerUserRegisterController`
    - `AjaxAccountUserController` to `AjaxCustomerUserController`
    - `AccountUserController` to `CustomerUserController`
    - `AccountUserProfileController` to `CustomerUserProfileController`
    - `AccountUserRoleController` to `CustomerUserRoleController`
    - `AbstractAjaxAccountUserController` to `AbstractAjaxCustomerUserController`
    - `AccountAccountUserSearchHandler` to `CustomerUserSearchHandler`
    - `ParentAccountSearchHandler` to `ParentCustomerSearchHandler`
    - `AccountGroupScopeExtension` to `CustomerGroupScopeExtension`
    - `AccountScopeExtension` to `CustomerScopeExtension`
    - `AccountGroupHandler` to `CustomerGroupHandler`
    - `FrontendAccountUserHandler` to `FrontendCustomerUserHandler`
    - `AccountUserRoleUpdateHandler` to `CustomerUserRoleUpdateHandler`
    - `AccountUserRoleUpdateFrontendHandler` to `CustomerUserRoleUpdateFrontendHandler`
    - `AbstractAccountUserPasswordHandler` to `AbstractCustomerUserPasswordHandler`
    - `AccountUserHandler` to `CustomerUserHandler`
    - `AbstractAccountUserRoleHandler` to `AbstractCustomerUserRoleHandler`
    - `AccountUserPasswordRequestHandler` to `CustomerUserPasswordRequestHandler`
    - `AccountUserPasswordResetHandler` to `CustomerUserPasswordResetHandler`
    - `FixAccountAddressesDefaultSubscriber` to `FixCustomerAddressesDefaultSubscriber`
    - `AccountUserRoleSelectType` to `CustomerUserRoleSelectType`
    - `FrontendAccountUserType` to `FrontendCustomerUserType`
    - `AccountGroupType` to `CustomerGroupType`
    - `FrontendAccountUserRoleType` to `FrontendCustomerUserRoleType`
    - `FrontendAccountUserRegistrationType` to `FrontendCustomerUserRegistrationType`
    - `AccountUserRoleType` to `CustomerUserRoleType`
    - `FrontendAccountTypedAddressType` to `FrontendCustomerTypedAddressType`
    - `AccountUserSelectType` to `CustomerUserSelectType`
    - `AbstractAccountUserRoleType` to `AbstractCustomerUserRoleType`
    - `FrontendAccountUserRoleSelectType` to `FrontendCustomerUserRoleSelectType`
    - `AccountType` to `CustomerType`
    - `AccountTypedAddressWithDefaultType` to `CustomerTypedAddressWithDefaultType`
    - `AccountTypedAddressType` to `CustomerTypedAddressType`
    - `FrontendAccountUserTypedAddressType` to `FrontendCustomerUserTypedAddressType`
    - `AccountUserType` to `CustomerUserType`
    - `AccountUserMultiSelectType` to `CustomerUserMultiSelectType`
    - `AccountSelectType` to `CustomerSelectType`
    - `FrontendAccountUserProfileType` to `FrontendCustomerUserProfileType`
    - `AccountGroupSelectType` to `CustomerGroupSelectType`
    - `AccountUserPasswordResetType` to `CustomerUserPasswordResetType`
    - `ParentAccountSelectType` to `ParentCustomerSelectType`
    - `AccountAclAccessLevelTextType` to `CustomerAclAccessLevelTextType`
    - `AccountUserPasswordRequestType` to `CustomerUserPasswordRequestType`
    - `AccountUserRoleVoter` to `CustomerUserRoleVoter`
    - `AccountGroupVoter` to `CustomerGroupVoter`
    - `AccountVoter` to `CustomerVoter`
* The method `ActionPermissionProvider::getAccountUserRolePermission` was renamed to `OrderAddressType::getCustomerUserRolePermission`.
* `Oro\Bundle\CustomerBundle\Controller\Frontend\Api\Rest\GridViewController`
    - added api controller based on `Oro\Bundle\DataGridBundle\Controller\Api\Rest\GridViewController ` and override methods:
        postAction(), putAction(), deleteAction(), defaultAction()
* `Oro\Bundle\CustomerBundle\Datagrid\Extension\GridViewsExtension`
    - added class based on `Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension`
* `Oro\Bundle\CustomerBundle\Datagrid\Extension\GridViewsExtensionComposite`
    - added class based on `Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension` and override methods:
        isApplicable(), getPriority(), visitMetadata(), setParameters()
* `Oro\Bundle\CustomerBundle\Entity\GridView`
    - added entity class based on `Oro\Bundle\DataGridBundle\Entity\AbstractGridView` with new field `customer_user_owner_id`
* `Oro\Bundle\CustomerBundle\Entity\GridViewUser`
    - added entity class based on `Oro\Bundle\DataGridBundle\Entity\AbstractGridView` with new field `customer_user_id`
* `Oro\Bundle\CustomerBundle\Entity\Manager\GridViewManagerComposite`
    - added class based on `Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager` and override methods:
        setDefaultGridView(), getSystemViews(), getAllGridViews(), getDefaultView(), getView()
* `Oro\Bundle\CustomerBundle\Entity\Repository\GridViewRepository`
    - added repository class based on `Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository` with replaced getOwnerFieldName() and getUserFieldName() to `customerUserOwner` and `customerUser`
* `Oro\Bundle\CustomerBundle\Entity\Repository\GridViewUserRepository`
    - added repository class based on `Oro\Bundle\DataGridBundle\Entity\Repository\GridViewUserRepository` with replaced getUserFieldName() to `customerUser`

FrontendBundle
--------------
* New method `getPackagePaths` was added to `TranslationPackagesProviderExtension` class.

InvoiceBundle
-------------
* The following methods were renamed in `Invoice` entity:
    - `getAccountUser`/`setAccountUser` to `getCustomerUser`/`setCustomerUser`
    - `getAccount`/`setAccount` to `getCustomer`/`setCustomer`

OrderBundle
-----------
* The following methods were renamed in `OrderAddressSecurityProvider` class:
    - `isAccountAddressGranted` to `isCustomerAddressGranted`
    - `isAccountUserAddressGranted` to `isCustomerUserAddressGranted`
* The following methods were renamed in `OrderAddressProvider` class:
    - `getAccountAddresses` to `getCustomerAddresses`
    - `getAccountUserAddresses` to `getCustomerUserAddresses`
* The following protected methods were renamed in `OrderAddressProvider` class:
    - `getAccountAddressRepository` to `getCustomerAddressRepository`
    - `getAccountUserAddressRepository` to `getCustomerUserAddressRepository`
* The following protected methods were renamed in `AddressProviderInterface` class:
    - `getAccountAddresses` to `getCustomerAddresses`
    - `getAccountUserAddresses` to `getCustomerUserAddresses`
* The following methods were renamed in `Order` entity:
    - `getAccountUser`/`setAccountUser` to `getCustomerUser`/`setCustomerUser`
    - `getAccount`/`setAccount` to `getCustomer`/`setCustomer`
* New methods `getParentProduct` and `setParentProduct` were added to `OrderLineItem` entity.
* The following methods were renamed in `Order` entity:
    - `getAccountUser`/`setAccountUser` to `getCustomerUser`/`setCustomerUser`
    - `getAccount`/`setAccount` to `getCustomer`/`setCustomer`
* The following methods were renamed in `OrderAddress` entity:
    - `getAccountAddress`/`setAccountAddress` to `getCustomerAddress`/`setCustomerAddress`
    - `getAccountUserAddress`/`setAccountUserAddress` to `getCustomerUserAddress`/`setCustomerUserAddress`
* The following methods were renamed in `FormViewListener` event listener:
    - `onAccountUserView` to `onCustomerUserView`
    - `onAccountView` to `onCustomerView`
* The method `convertPricesToArray` was removed from event listener `OrderPossibleShippingMethodsEventListener`.
* The method `OrderPossibleShippingMethodsEventListener::__construct` has been updated. Pass `ShippingPricesConverter` as a first argument of the method.
* The following methods were renamed in class `OrderRequestHandler`:
    - `getAccount` to `getCustomer`
    - `getAccountUser` to `getCustomerUser`
* The following methods were renamed in class `FrontendOrderDataHandler`:
    - `getAccount` to `getCustomer`
    - `getAccountUser` to `getCustomerUser`
* The protected method `OrderAddressType::initAccountAddressField` was renamed to `OrderAddressType::initCustomerAddressField`.
* The protected method `AbstractOrderAddressType::initAccountAddressField` was renamed to `AbstractOrderAddressType::initCustomerAddressField`.

PaymentBundle
-------------
* The protected method `PaymentTransactionProvider::getLoggedAccountUser` was renamed to `PaymentTransactionProvider::getLoggedCustomerUser`.

PaymentTermBundle
-----------------
* The following classes were renamed:
    - `AccountDatagridListener` to `CustomerDatagridListener`
    - `AccountFormExtension` to `CustomerFormExtension`
* The following methods were renamed in `DeleteMessageTextGenerator` class:
    - `generateAccountGroupFilterUrl` to `generateCustomerGroupFilterUrl`
    - `generateAccountFilterUrl` to `generateCustomerFilterUrl`
* The following methods were renamed in `PaymentTermProvider` class:
    - `getAccountPaymentTerm` to `getCustomerPaymentTerm`
    - `getAccountGroupPaymentTerm` to `getCustomerGroupPaymentTerm`
    - `getAccountPaymentTermByOwner` to `getCustomerPaymentTermByOwner`
    - `getAccountGroupPaymentTermByOwner` to `getCustomerGroupPaymentTermByOwner`
* The following protected methods were renamed in `PaymentTermExtension` class:
    - `getAccountPaymentTermId` to `getCustomerPaymentTermId`
    - `getAccountGroupPaymentTermId` to `getCustomerGroupPaymentTermId`

PayPalBundle
------------
* The class `Account` was renamed to `Customer`.

PricingBundle
-------------
* The following classes were renamed:
    - `AccountCombinedPriceListsBuilder` to `CustomerCombinedPriceListsBuilder`
    - `AccountGroupCombinedPriceListsBuilder` to `CustomerGroupCombinedPriceListsBuilder`
    - `AccountCPLUpdateEvent` to `CustomerCPLUpdateEvent`
    - `AccountGroupCPLUpdateEvent` to `CustomerGroupCPLUpdateEvent`
    - `CombinedPriceListToAccount` to `CombinedPriceListToCustomer`
    - `CombinedPriceListToAccountGroup` to `CombinedPriceListToCustomerGroup`
    - `PriceListAccountFallback` to `PriceListCustomerFallback`
    - `PriceListAccountGroupFallback` to `PriceListCustomerGroupFallback`
    - `CombinedPriceListToAccountGroupRepository` to `CombinedPriceListToCustomerGroupRepository`
    - `PriceListAccountGroupFallbackRepository` to `PriceListCustomerGroupFallbackRepository`
    - `PriceListAccountFallbackRepository` to `PriceListCustomerFallbackRepository`
    - `PriceListToAccountRepository` to `PriceListToCustomerRepository`
    - `PriceListToAccountGroupRepository` to `PriceListToCustomerGroupRepository`
    - `CombinedPriceListToAccountRepository` to `CombinedPriceListToCustomerRepository`
    - `PriceListToAccountGroup` to `PriceListToCustomerGroup`
    - `PriceListToAccount` to `PriceListToCustomer`
    - `AccountGroupDataGridListener` to `CustomerGroupDataGridListener`
    - `AbstractAccountFormViewListener` to `AbstractCustomerFormViewListener`
    - `AccountGroupListener` to `CustomerGroupListener`
    - `AccountGroupFormViewListener` to `CustomerGroupFormViewListener`
    - `AccountListener` to `CustomerListener`
    - `AccountFormViewListener` to `CustomerFormViewListener`
    - `AccountDataGridListener` to `CustomerDataGridListener`
    - `AccountWebsiteDTO` to `CustomerWebsiteDTO`
    - `AccountGroupFormExtension` to `CustomerGroupFormExtension`
    - `AccountFormExtension` to `CustomerFormExtension`
* The method `WebsiteCombinedPriceListsBuilder::setAccountGroupCombinedPriceListsBuilder` was renamed to `WebsiteCombinedPriceListsBuilder::setCustomerGroupCombinedPriceListsBuilder`.
* The following methods were renamed in `PriceListCollectionProvider` class:
    - `getPriceListsByAccountGroup` to `getPriceListsByCustomerGroup`
    - `getPriceListsByAccount` to `getPriceListsByCustomer`
* The following protected methods were renamed in `PriceListCollectionProvider` class:
    - `isFallbackToCurrentAccountOnly` to `isFallbackToCurrentCustomerOnly`
    - `isFallbackToAccountGroup` to `isFallbackToCustomerGroup`
* The following methods were renamed in `CombinedPriceListRepository` class:
    - `getPriceListByAccount` to `getPriceListByCustomer`
    - `getPriceListByAccountGroup` to `getPriceListByCustomerGroup`
* New method `getCPLsForPriceCollectByTimeOffsetCount` was added to class `CombinedPriceListRepository`.
* New protected method `getCPLsForPriceCollectByTimeOffsetQueryBuilder` was added to class `CombinedPriceListRepository`.
* The following methods were renamed in `DatagridListener` event listener:
    - `onBuildBeforeAccounts` to `onBuildBeforeCustomers`
    - `onBuildBeforeAccountGroups` to `onBuildBeforeCustomerGroups`
* The following methods were renamed in `CombinedPriceListProcessor` class:
    - `dispatchAccountScopeEvent` to `dispatchCustomerScopeEvent`
    - `dispatchAccountGroupScopeEvent` to `dispatchCustomerGroupScopeEvent`
* The following methods were renamed in `PriceListTreeHandler` class:
    - `getPriceListByAccount` to `getPriceListByCustomer`
    - `getPriceListByAccountGroup` to `getPriceListByCustomerGroup`
* The following methods were renamed in `PriceListRelationTriggerHandler` class:
    - `handleAccountChange` to `handleCustomerChange`
    - `handleAccountGroupChange` to `handleCustomerGroupChange`
    - `handleAccountGroupRemove` to `handleCustomerGroupRemove`
* The method `PriceListRequestHandlerInterface::getPriceListByAccount` was renamed to `PriceListRequestHandlerInterface::getPriceListByCustomer`.
* The following methods were renamed in `PriceListRelationTrigger` class:
    - `getAccount`/`setAccount` to `getCustomer`/`setCustomer`
    - `getAccountGroup`/`setAccountGroup` to `getCustomerGroup`/`setCustomerGroup`
* The method `PriceListRequestHandler::getPriceListByAccount` was renamed to `PriceListRequestHandler::getPriceListByCustomer`.
* The protected method `PriceListRequestHandler::getAccount` was renamed to `PriceListRequestHandler::getCustomer`.
* New method `isActive` was added to class `CombinedPriceListScheduleCommand`.
* The following methods were renamed in `PriceListRelationTrigger` class:
    - `getAccounts` to `getCustomers`
    - `getAccountGroups` to `getCustomerGroups`
* The method `AjaxProductPriceController::getProductPricesByAccount` was renamed to `AjaxProductPriceController::getProductPricesByCustomer`.
* The method `AbstractAjaxProductPriceController::getProductPricesByAccount` was renamed to `AbstractAjaxProductPriceController::getProductPricesByCustomer`.
* New protected methods `getAllProductEnabledUnits` and `getProductForm` were added to form type `ProductPriceUnitSelectorType`.

ProductBundle
-------------
* The classes `FrontendVariantFiledType` and `ProductCustomVariantFieldsChoiceType` were removed.
* The method `getName` was removed from twig extension `ProductUnitValueExtension`.
* The method `ProductUnitValueExtension::__construct` has been updated. Pass `UnitValueFormatterInterface` as a first argument of the method instead of `ProductUnitValueFormatter`.
* New method `getProductVariantOrProduct` was added to class `ProductVariantProvider`.
* New method `__construct` was added to class `ProductFormProvider`.
* The following methods were removed from `ProductVariantAvailabilityProvider` class:
    - `getVariantFieldsWithAvailability`
    - `filterVariants`
    - `getVariantFields`
    - `getAllVariantsByVariantFieldName`
    - `getFieldType`
* New methods `getVariantFieldsAvailability` and `getCustomFieldType` were added to class `ProductVariantAvailabilityProvider`.
* The method `ProductVariantAvailabilityProvider::getSimpleProductByVariantFields` has been updated. Pass `boolean` as a third argument of the method.
* New method `getVariantFieldsValuesForVariant` was added to class `ProductVariantAvailabilityProvider`.
* The method `SystemDefaultProductUnitProvider::__construct` has been updated. Pass `\Oro\Bundle\EntityBundle\ORM\DoctrineHelper` as a second argument of the method instead of `\Doctrine\Common\Persistence\ManagerRegistry`.
* New methods `getProductWithNamesBySkuQueryBuilder` and `findByCaseInsensitive` were added to entity repository `ProductRepository`.
* New method `isUpdatedAtSet` was added to `Product` entity.
* The method `ProductController::viewAction` has been updated. Pass `\Symfony\Component\HttpFoundation\Request` as a first argument of the method.
* New methods `__construct` and `checkUnitSelectionVisibility` were added to form type `FrontendLineItemType`.
* New protected method `getProduct` was added to form type `FrontendLineItemType`.

RFPBundle
---------
* The class `AccountViewListener` was renamed to `CustomerViewListener`.
* The following methods were renamed in `RequestRepresentativesNotifier` class:
    - `shouldNotifySalesRepsOfAccount` to `shouldNotifySalesRepsOfCustomer`
    - `shouldNotifyOwnerOfAccountUser` to `shouldNotifyOwnerOfCustomerUser`
    - `shouldNotifyOwnerOfAccount` to `shouldNotifyOwnerOfCustomer`
* New method `setLogger` was added to class `Processor`.
* The following methods were renamed in `Request` entity:
    - `getAssignedAccountUsers`/`addAssignedAccountUser`/`removeAssignedAccountUser` to `getAssignedCustomerUsers`/`addAssignedCustomerUser`/`removeAssignedCustomerUser`
    - `getAccountUser`/`setAccountUser` to `getCustomerUser`/`setCustomerUser`
    - `getAccount`/`setAccount` to `getCustomer`/`setCustomer`

SaleBundle
----------
* The class `AccountViewListener` was renamed to `CustomerViewListener`.
* The following methods were renamed in `QuoteAddressSecurityProvider` class:
    - `isAccountAddressGranted` to `isCustomerAddressGranted`
    - `isAccountUserAddressGranted` to `isCustomerUserAddressGranted`
* The following methods were renamed in `QuoteAddress` entity:
    - `getAccountAddress`/`setAccountAddress` to `getCustomerAddress`/`setCustomerAddress`
    - `getAccountUserAddress`/`setAccountUserAddress` to `getCustomerUserAddress`/`setCustomerUserAddress`
* The following methods were renamed in `QuoteProduct` entity:
    - `getCommentAccount`/`setCommentAccount` to `getCommentCustomer`/`setCommentCustomer`
* The following methods were renamed in `Quote` entity:
    - `getAssignedAccountUsers`/`addAssignedAccountUser`/`removeAssignedAccountUser` to `getAssignedCustomerUsers`/`addAssignedCustomerUser`/`removeAssignedCustomerUser`
    - `getAccountUser`/`setAccountUser` to `getCustomerUser`/`setCustomerUser`
    - `getAccount`/`setAccount` to `getCustomer`/`setCustomer`
* The following methods were removed from `Quote` entity:
    - `getShippingEstimate`/`setShippingEstimate`/`updateShippingEstimate`
    - `postLoad`
* The following methods were added to `Quote` entity:
    - `getCurrency`/`setCurrency`
    - `getShippingMethod`/`setShippingMethod`
    - `getShippingMethodType`/`setShippingMethodType`
    - `getShippingCost`
    - `getEstimatedShippingCost`
    - `getEstimatedShippingCostAmount`/`setEstimatedShippingCostAmount`
    - `getOverriddenShippingCostAmount`/`setOverriddenShippingCostAmount`
    - `getDemands`
    - `isAllowUnlistedShippingMethod`/`setAllowUnlistedShippingMethod`
    - `isShippingMethodLocked`/`setShippingMethodLocked`
    - `isOverriddenShippingCost`
* The following methods were renamed in `QuoteDemand` entity:
    - `getAccountUser`/`setAccountUser` to `getCustomerUser`/`setCustomerUser`
    - `getAccount`/`setAccount` to `getCustomer`/`setCustomer`
* The following methods were renamed in `QuoteRequestHandler` class:
    - `getAccount` to `getCustomer`
    - `getAccountUser` to `getCustomerUser`
* The protected method `AjaxQuoteController::getAccount` was renamed to `AjaxQuoteController::getCustomer`.
* New method `entryPointAction` was added to class `AjaxQuoteController`.
* The method `getTotalProcessor` was removed from class `QuoteController`.
* New protected method `getSubtotalsCalculator` was added to class `QuoteController`.
* The method `QuoteProductDemandOfferChoiceType::__construct` has been updated. Pass `\Oro\Bundle\ProductBundle\Visibility\UnitVisibilityInterface` as a third argument of the method.
* The method `QuoteType::__construct` has been updated. Pass `\Oro\Bundle\ConfigBundle\Config\ConfigManager` as a second argument of the method.
* New protected method `addShippingFields` was added to form type `QuoteType`.

ShippingBundle
--------------
* The following classes were moved to `FlatRateBundle`:
    - `FlatRateShippingMethodType`
    - `FlatRateShippingMethod`
    - `FlatRateShippingMethodProvider`
    - `FlatRateShippingMethodTypeOptionsType`
* The methods `getApplicableMethodsWithTypesData` and `getMethodTypesConfigs` were removed from class `ShippingPriceProvider`.
* New method `getApplicableMethodsViews` was added to class `ShippingPriceProvider`.
* New protected method `getApplicableMethodTypesViews` was added to class `ShippingPriceProvider`.
* The method `ShippingPriceProvider::__construct` has been updated. Pass `ShippingMethodViewFactory` as a fourth argument of the method.
* New method `formatShippingMethodWithTypeLabel` was added to class `ShippingMethodLabelFormatter`.

ShoppingListBundle
------------------
* The class `AccountUserShoppingListsProvider` was renamed to `CustomerUserShoppingListsProvider`.
* The method `ShoppingListRepository::findAvailableForAccountUser` was renamed to `ShoppingListRepository::findAvailableForCustomerUser`.
* The method `ShoppingListTotalRepository::invalidateByAccounts` was renamed to `ShoppingListTotalRepository::invalidateByCustomers`.
* The following methods were renamed in `ShoppingList` entity:
    - `getAccountUser`/`setAccountUser` to `getCustomerUser`/`setCustomerUser`
    - `getAccount`/`setAccount` to `getCustomer`/`setCustomer`
* The following methods were renamed in `LineItem` entity:
    - `getAccountUser`/`setAccountUser` to `getCustomerUser`/`setCustomerUser`
* New methods `getParentProduct` and `setParentProduct` were added to `LineItem` entity.
* The protected method `FrontendProductDatagridListener::getLoggedAccountUser` was renamed to `FrontendProductDatagridListener::getLoggedCustomerUser`.
* The following methods were renamed in `ShoppingListTotalListener` entity:
    - `onAccountPriceListUpdate` to `onCustomerPriceListUpdate`
    - `onAccountGroupPriceListUpdate` to `onCustomerGroupPriceListUpdate`
* New protected method `getParentProduct` was added to `AjaxLineItemController` class.
* The protected method `ShoppingListManager::getAccountUser` was renamed to `ShoppingListManager::getCustomerUser`.

TaxBundle
---------
* The following classes were renamed:
    - `AccountTaxCodeRepository` to `CustomerTaxCodeRepository`
    - `AccountTaxCode` to `CustomerTaxCode`
    - `AccountGroupFormViewListener` to `CustomerGroupFormViewListener`
    - `AccountTaxCodeGridListener` to `CustomerTaxCodeGridListener`
    - `AccountFormViewListener` to `CustomerFormViewListener`
    - `AccountTaxCodeController` to `CustomerTaxCodeController`
    - `AccountGroupTaxExtension` to `CustomerGroupTaxExtension`
    - `AccountTaxExtension` to `CustomerTaxExtension`
    - `AccountTaxCodeAutocompleteType` to `CustomerTaxCodeAutocompleteType`
    - `AccountTaxCodeType` to `CustomerTaxCodeType`
* The following methods were renamed in `TaxRule` entity:
    - `getAccountTaxCode`/`setAccountTaxCode` to `getCustomerTaxCode`/`setCustomerTaxCode`
* The protected method `OrderLineItemHandler::getAccountTaxCode` was renamed to `OrderLineItemHandler::getCustomerTaxCode`.
* The protected method `OrderHandler::getAccountTaxCode` was renamed to `OrderHandler::getCustomerTaxCode`.
* New method `postLoad` was added to `TaxValue` entity.
* New methods `jsonDeserialize` and `jsonSerialize` were added to `Result` class.
* New protected methods `deserializeAsResultElement` and `prepareToSerialization` were added to `Result` class.
* New method `jsonSerialize` was added to `ResultElement` class.
* The method `TotalResolver::adjustAmounts` has been updated. Pass `AbstractResultElement` as a first argument of the method instead of `ResultElement`. Pass `\Brick\Math\BigDecimal` as a second argument of the method.

VisibilityBundle
----------------
* The following classes were renamed:
    - `AccountListener` to `CustomerListener`
    - `AccountCategoryVisibilityResolved` to `CustomerCategoryVisibilityResolved`
    - `AccountProductRepository` to `CustomerProductRepository`
    - `AccountGroupCategoryRepository` to `CustomerGroupCategoryRepository`
    - `AccountCategoryRepository` to `CustomerCategoryRepository`
    - `AccountGroupProductRepository` to `CustomerGroupProductRepository`
    - `AccountGroupCategoryVisibilityResolved` to `CustomerGroupCategoryVisibilityResolved`
    - `AccountProductVisibilityResolved` to `CustomerProductVisibilityResolved`
    - `AccountGroupProductVisibilityResolved` to `CustomerGroupProductVisibilityResolved`
    - `AccountGroupProductVisibility` to `CustomerGroupProductVisibility`
    - `AccountGroupCategoryVisibilityRepository` to `CustomerGroupCategoryVisibilityRepository`
    - `AccountGroupProductVisibilityRepository` to `CustomerGroupProductVisibilityRepository`
    - `AccountProductVisibilityRepository` to `CustomerProductVisibilityRepository`
    - `AccountCategoryVisibilityRepository` to `CustomerCategoryVisibilityRepository`
    - `AccountCategoryVisibility` to `CustomerCategoryVisibility`
    - `AccountGroupCategoryVisibility` to `CustomerGroupCategoryVisibility`
    - `AccountProductVisibility` to `CustomerProductVisibility`
    - `AccountProcessor` to `CustomerProcessor`
    - `AccountMessageFactory` to `CustomerMessageFactory`
    - `AccountGroupProductResolvedCacheBuilder` to `CustomerGroupProductResolvedCacheBuilder`
    - `AccountCategoryResolvedCacheBuilder` to `CustomerCategoryResolvedCacheBuilder`
    - `VisibilityChangeAccountSubtreeCacheBuilder` to `VisibilityChangeCustomerSubtreeCacheBuilder`
    - `AccountGroupCategoryResolvedCacheBuilder` to `CustomerGroupCategoryResolvedCacheBuilder`
    - `AccountProductResolvedCacheBuilder` to `CustomerProductResolvedCacheBuilder`
    - `OrmAccountPartialUpdateDriver` to `OrmCustomerPartialUpdateDriver`
    - `AccountPartialUpdateDriverInterface` to `CustomerPartialUpdateDriverInterface`
    - `AbstractAccountPartialUpdateDriver` to `AbstractCustomerPartialUpdateDriver`
* The following methods were renamed in `VisibilityScopeProvider` class:
    - `getAccountProductVisibilityScope` to `getCustomerProductVisibilityScope`
    - `getAccountGroupProductVisibilityScope` to `getCustomerGroupProductVisibilityScope`
* The following methods were renamed in `ProductDuplicateListener` event listener:
    - `setVisibilityAccountClassName` to `setVisibilityCustomerClassName`
    - `setVisibilityAccountGroupClassName` to `setVisibilityCustomerGroupClassName`
* The following methods were renamed in `CategoryVisibilityResolverInterface` class:
    - `isCategoryVisibleForAccountGroup` to `isCategoryVisibleForCustomerGroup`
    - `getVisibleCategoryIdsForAccountGroup` to `getVisibleCategoryIdsForCustomerGroup`
    - `getHiddenCategoryIdsForAccountGroup` to `getHiddenCategoryIdsForCustomerGroup`
    - `isCategoryVisibleForAccount` to `isCategoryVisibleForCustomer`
    - `getVisibleCategoryIdsForAccount` to `getVisibleCategoryIdsForCustomer`
    - `getHiddenCategoryIdsForAccount` to `getHiddenCategoryIdsForCustomer`
* The following methods were renamed in `ProductVisibilityProvider` class:
    - `getAccountVisibilitiesForProducts` to `getCustomerVisibilitiesForProducts`
    - `getAccountProductsVisibilitiesByWebsiteQueryBuilder` to `getCustomerProductsVisibilitiesByWebsiteQueryBuilder`
* The following methods were renamed in `PositionChangeCategorySubtreeCacheBuilder`class:
    - `setAccountCategoryRepository` to `setCustomerCategoryRepository`
    - `getAccountGroupCategoryRepository` to `getCustomerGroupCategoryRepository`
    - `setAccountGroupCategoryRepository` to `setCustomerGroupCategoryRepository`
* The following methods were renamed in `CategoryVisibilityResolver`class:
    - `isCategoryVisibleForAccountGroup` to `isCategoryVisibleForCustomerGroup`
    - `getVisibleCategoryIdsForAccountGroup` to `getVisibleCategoryIdsForCustomerGroup`
    - `getHiddenCategoryIdsForAccountGroup` to `getHiddenCategoryIdsForCustomerGroup`
    - `isCategoryVisibleForAccount` to `isCategoryVisibleForCustomer`
    - `getVisibleCategoryIdsForAccount` to `getVisibleCategoryIdsForCustomer`
    - `getHiddenCategoryIdsForAccount` to `getHiddenCategoryIdsForCustomer`
* The following protected methods were renamed in `CategoryRepository` class:
    - `getAccountGroupCategoryVisibilityResolvedTerm` to `getCustomerGroupCategoryVisibilityResolvedTerm`
    - `getAccountCategoryVisibilityResolvedTerm` to `getCustomerCategoryVisibilityResolvedTerm`
* The following protected methods were renamed in `CategoryProcessor` class:
    - `setToDefaultAccountGroupProductVisibilityWithoutCategory` to `setToDefaultCustomerGroupProductVisibilityWithoutCategory`
    - `setToDefaultAccountProductVisibilityWithoutCategory` to `setToDefaultCustomerProductVisibilityWithoutCategory`
* The following protected methods were renamed in `ProductVisibilityQueryBuilderModifier` class:
    - `getAccountGroupProductVisibilityResolvedTerm` to `getCustomerGroupProductVisibilityResolvedTerm`
    - `getAccountProductVisibilityResolvedTerm` to `getCustomerProductVisibilityResolvedTerm`
* The following protected methods were renamed in `VisibilityChangeGroupSubtreeCacheBuilder` class:
    - `updateAccountGroupsFirstLevel` to `updateCustomerGroupsFirstLevel`
    - `updateAccountsFirstLevel` to `updateCustomersFirstLevel`
    - `getAccountIdsWithFallbackToCurrentGroup` to `getCustomerIdsWithFallbackToCurrentGroup`
* The following protected methods were renamed in `VisibilityChangeCategorySubtreeCacheBuilder` class:
    - `updateAccountGroupsFirstLevel` to `updateCustomerGroupsFirstLevel`
    - `getAccountGroupIdsFirstLevel` to `getCustomerGroupIdsFirstLevel`
    - `updateAccountsFirstLevel` to `updateCustomersFirstLevel`
    - `getAccountIdsFirstLevel` to `getCustomerIdsFirstLevel`
    - `getAccountGroupIdsWithFallbackToAll` to `getCustomerGroupIdsWithFallbackToAll`
* The following protected methods were renamed in `PositionChangeCategorySubtreeCacheBuilder` class:
    - `updateAccountGroupsAppropriateVisibility` to `updateCustomerGroupsAppropriateVisibility`
    - `updateAccountsAppropriateVisibility` to `updateCustomersAppropriateVisibility`
    - `getAccountCategoryRepository` to `getCustomerCategoryRepository`
* The following protected methods were renamed in `AbstractRelatedEntitiesAwareSubtreeCacheBuilder` class:
    - `updateAccountGroupsFirstLevel` to `updateCustomerGroupsFirstLevel`
    - `updateAccountsFirstLevel` to `updateCustomersFirstLevel`
    - `getCategoryAccountGroupIdsWithVisibilityFallbackToParent` to `getCategoryCustomerGroupIdsWithVisibilityFallbackToParent`
    - `getAccountIdsWithFallbackToParent` to `getCustomerIdsWithFallbackToParent`
    - `getAccountIdsWithFallbackToAll` to `getCustomerIdsWithFallbackToAll`
    - `getAccountIdsForUpdate` to `getCustomerIdsForUpdate`
    - `updateAccountGroupsProductVisibility` to `updateCustomerGroupsProductVisibility`
    - `updateAccountsProductVisibility` to `updateCustomersProductVisibility`
    - `updateAccountsCategoryVisibility` to `updateCustomersCategoryVisibility`
    - `updateAccountGroupsCategoryVisibility` to `updateCustomerGroupsCategoryVisibility`
* The protected method `CategoryVisibilityResolver::getGroupScopeByAccount` was renamed to `CategoryVisibilityResolver::getGroupScopeByCustomer`.
* The following protected methods were renamed in `VisibilityFormPostSubmitDataHandler` event listener:
    - `saveFormAccountGroupData` to `saveFormCustomerGroupData`
    - `saveFormAccountData` to `saveFormCustomerData`
* The protected method `FormViewListener::addAccountCategoryVisibilityBlock` was renamed to `FormViewListener::addCustomerCategoryVisibilityBlock`.
* The following protected methods were renamed in `VisibilityPostSetDataListener` event listener:
    - `saveFormAccountGroupData` to `saveFormCustomerGroupData`
    - `saveFormAccountData` to `saveFormCustomerData`

WebsiteSearchBundle
-------------------
* The class `Item` was removed.
* The class `AccountIdPlaceholder` was renamed to `CustomerIdPlaceholder`.
