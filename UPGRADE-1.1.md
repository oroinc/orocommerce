UPGRADE FROM 1.0.0 to 1.1
=======================================

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

CheckoutBundle
--------------
* `Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository`:
    - added third argument `string $workflowName` for method `public function findCheckoutByCustomerUserAndSourceCriteria()`

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

ShoppingListBundle
------------------
- Class `Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository`
    - changed signature of `invalidateTotals` method from `invalidateTotals(BufferedQueryResultIterator $iterator)` to `invalidateTotals(\Iterator $iterator)`

VisibilityBundle
----------------
- Class `Oro\Bundle\VisibilityBundle\Driver\AbstractCustomerPartialUpdateDriver`
    - changed the return type of `getCustomerVisibilityIterator` method from `BufferedQueryResultIterator` to `BufferedQueryResultIteratorInterface`

PaymentTermBundle
----------------
* Added interfaces:
    - `Oro/Bundle/PaymentTermBundle/Method/Config/Factory/Settings/PaymentTermConfigBySettingsFactoryInterface.php`
    - `Oro/Bundle/PaymentTermBundle/Method/Config/Provider/PaymentTermConfigProviderInterface.php`
    - `Oro/Bundle/PaymentTermBundle/Method/Factory/PaymentTermPaymentMethodFactoryInterface.php`
    - `Oro/Bundle/PaymentTermBundle/Method/View/Factory/PaymentTermPaymentMethodViewFactoryInterface.php`
* Removed classes:
    - `Oro/Bundle/PaymentTermBundle/DependencyInjection/Configuration.php`
        - it is useless because PaymentTerm payment methods settings were moved from system configuration to integrations
    - `Oro/Bundle/PaymentTermBundle/Method/Config/PaymentTermConfig.php`
* Added classes:
    - `Oro/Bundle/PaymentTermBundle/Entity/PaymentTermSettings.php`
        - here moved all settings from system configuration related to PaymentTerm payment methods
    - `Oro/Bundle/PaymentTermBundle/Entity/Repository/PaymentTermSettingsRepository.php`
        - added method findWithEnabledChannel()
    - `Oro/Bundle/PaymentTermBundle/Form/Type/PaymentTermSettingsType.php`
        - form type responsible for filling PaymentTerm payment methods settings
    - `Oro/Bundle/PaymentTermBundle/Integration/PaymentTermChannelType.php`
    - `Oro/Bundle/PaymentTermBundle/Integration/PaymentTermTransport.php`
    - `Oro/Bundle/PaymentTermBundle/Method/Config/ParameterBag/Factory/Settings/ParameterBagPaymentTermConfigBySettingsFactory.php`
    - `Oro/Bundle/PaymentTermBundle/Method/Config/ParameterBag/ParameterBagPaymentTermConfig.php`
    - `Oro/Bundle/PaymentTermBundle/Method/Config/Provider/Basic/BasicPaymentTermConfigProvider.php`
    - `Oro/Bundle/PaymentTermBundle/Method/Config/Provider/Cached/Memory/CachedMemoryPaymentTermConfigProvider.php`
    - `Oro/Bundle/PaymentTermBundle/Method/Factory/PaymentTermPaymentMethodFactory.php`
    - `Oro/Bundle/PaymentTermBundle/Method/Provider/PaymentTermMethodProvider.php `
    - `Oro/Bundle/PaymentTermBundle/Method/View/Factory/PaymentTermPaymentMethodViewFactory.php`
    - `Oro/Bundle/PaymentTermBundle/Method/View/Provider/PaymentTermMethodViewProvider.php`
    - `Oro/Bundle/PaymentTermBundle/Migrations/Data/ORM/MoveConfigValuesToSettings.php`
        - data migration responsible to move PaymentTerm payment methods settings from system configuration to integrations
* Modified classes:
    - `Oro/Bundle/PaymentTermBundle/Method/PaymentTerm.php`
        - modified method getIdentifier (identifier is taken from PaymentTermConfig)
    - `Oro/Bundle/PaymentTermBundle/Method/View/PaymentTermView.php`
        - removed methods:
            - getPaymentMethodType()
        - added methods:
            - getAdminLabel()
            - getPaymentMethodIdentifier()
