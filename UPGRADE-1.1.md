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
- Added Configurable Permission `commerce` for View and Edit pages of Customer Role in backend area (see [configurable-permissions.md](../platform/src/Oro/Bundle/SecurityBundle/Resources/doc/configurable-permissions.md) for details.
- Added Configurable Permission `commerce_frontend` for View and Edit pages of Customer Role in frontend area (see [configurable-permissions.md](../platform/src/Oro/Bundle/SecurityBundle/Resources/doc/configurable-permissions.md) for details.

FlatRateBundle
-------------------
- Change name of the bundle to FlatRateShippingBundle

WebsiteSearchBundle
-------------------
- Driver::writeItem() and Driver::flushWrites() should be used instead of Driver::saveItems()

FrontendTestFrameworkBundle
---------------------------
- 'Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase' method `tearDown` renamed to `afterFrontendTest`

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
