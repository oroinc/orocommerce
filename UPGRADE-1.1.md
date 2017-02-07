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

FlatRateBundle
-------------------
- Change name of the bundle to FlatRateShippingBundle

WebsiteSearchBundle
-------------------
- Driver::writeItem() and Driver::flushWrites() should be used instead of Driver::saveItems()

FrontendTestFrameworkBundle
---------------------------
- 'Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase' method `tearDown` renamed to `afterFrontendTest`

RedirectBundle
--------------
- `Oro\Bundle\RedirectBundle\Entity\Redirect`
    - removed property `website` in favour of `scopes` collection using