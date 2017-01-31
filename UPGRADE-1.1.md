UPGRADE FROM 1.0.0 to 1.1
=======================================


FlatRateBundle
-------------------
- Change name of the bundle to FlatRateShippingBundle

WebsiteSearchBundle
-------------------
- Driver::writeItem() and Driver::flushWrites() should be used instead of Driver::saveItems()

RedirectBundle
--------------
- `Oro\Bundle\RedirectBundle\Entity\Redirect`
    - removed property `website` in favour of `scopes` collection using