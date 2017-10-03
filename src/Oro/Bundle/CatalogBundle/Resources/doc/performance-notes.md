Performance notes
=================
This document contains notes about improving performance of catalogs
in large datasets.

### Catalog menu caching

The category tree is cached for complex menus, when oro_fallback_localization_val table has millions of records. You can control this cache to be enabled/disabled on demand.

You can control default lifetime of the cache in `Resources/config/layout.yml` file. This value is expressed in seconds, so by setting:

    oro_catalog.layout.data_provider.category:
            [...]
            - [setCache, ['@oro_catalog.layout.data_provider.category.cache', 3600]]

you set a `1h` cache for category menu. 

The default value of lifeTime parameter is `0` - means unlimited caching and it invalidates only when categories are modified.
