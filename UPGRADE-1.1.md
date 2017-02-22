UPGRADE FROM 1.0.0 to 1.1
=======================================

Tree Component
--------------
- `Oro\Component\Tree\Handler\AbstractTreeHandler`:
    - added method `getTreeItemList`

CatalogBundle
-------------
- Class `Oro\Bundle\CatalogBundle\Twig\CategoryExtension`
    - the construction signature of was changed. Now the constructor has `ContainerInterface $container` parameter
    - removed method `setContainer`


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

OrderBundle
-----------
- Class `Oro\Bundle\OrderBundle\Twig\OrderExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $sourceDocumentFormatter`
    - removed property `protected $shippingTrackingFormatter`
- Class `Oro\Bundle\OrderBundle\Twig\OrderShippingExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed method `setShippingLabelFormatter`

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

PaymentTermBundle
-----------------
- Class `Oro\Bundle\PaymentTermBundle\Twig\DeleteMessageTextExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $deleteMessageGenerator`

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

SaleBundle
----------
- Class `Oro\Bundle\SaleBundle\Twig\QuoteExtension`
    - the construction signature of was changed. Now the constructor has only `ContainerInterface $container` parameter
    - removed property `protected $quoteProductFormatter`
    - removed property `protected $configManager`

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
