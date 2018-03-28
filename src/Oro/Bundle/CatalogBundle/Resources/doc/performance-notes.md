# Performance notes

This document contains notes about improving performance of catalogs in large datasets.

### Catalog Menu Caching

The category tree is cached for complex menus when the oro_fallback_localization_val table has a great number of records. You can enable or disable the cache on demand.

You can control the default lifetime of cache in the `Resources/config/layout.yml` file. This value is set in seconds. So when configuring

    oro_catalog.layout.data_provider.category:
            [...]
            - [setCache, ['@oro_catalog.layout.data_provider.category.cache', 3600]]

you set the `3600` instead of the `1h` cache for a category menu. 

The `0` parameter set as a default value for cache lifetime means that the cache saving time is unlimited. It becomes invalidated once a category is modified. 
