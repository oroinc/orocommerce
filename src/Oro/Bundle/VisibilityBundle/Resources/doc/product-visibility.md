Product visibility
------------------

Product visibility is a functionality that allows to show or hide some products in specific scope.


### General Information

There are 3 levels of visibility for each product in scope types: product_visibility, account_product_visibility, account_group_product_visibility;
Product visibility edit page allows setting of the following values:

####Product Visibility (Visibility to All):
* **Category (Visibility to All)** - `Default value`. Value is taken from the category of this product
* **Config** - The value is taken from the system configuration parameter "Product Visibility to Accounts"
* **Hidden** - Specific static value
* **Visible** - Specific static value

####Visibility to Account Groups:
* **Current Product (Visibility to All)** - `Default value`. Fallback to Product Visibility (Visibility to All) value
* **Category** - Value is taken from the category of this product for selected account group
* **Hidden** - Specific static value
* **Visible** - Specific static value

####Visibility to Accounts:
* **Account Group (Visibility to this Account's Group)** - `Default value`. Fallback to Visibility to Account Groups
* **Current Product (Visibility to All)**  - Fallback to Product Visibility (Visibility to All) value
* **Category (Visibility to this Account)** - Value is taken from the category of this product for selected account
* **Hidden** - Specific static value
* **Visible** - Specific static value

There is entities in database for each listed levels:
`ProductVisibility`, `AccountProductVisibility`, `AccountGroupProductVisibility` each of which implements 
`VisibilityInterface` and `ScopeAwareInterface` interfaces.

####Addition information:
* If default value is selected then the entity is not written to the database.
* If product doesn't have category, then "Category" option is not available for all levels. 
If visibility setting already exist with "Category" value, and category is deleted for specific product, 
then setting value is changed to default value (for visibility to account and visibility to account group), 
for visibility to all that value changed to "Config".     


### Product Visibility Cache

Resolved visibility settings must be pre-calculated and cached for greater performance. 
To do this there are 3 resolved entities that contains only static visibility values (visible or hidden).

There are resolved entities in database for each level:
`ProductVisibilityResolved`, `AccountProductVisibilityResolved`, `AccountGroupProductVisibilityResolved` 
each of which extend `BaseProductVisibilityResolved` abstract class.

For `AccountProductVisibilityResolved` and `AccountGroupProductVisibilityResolved` in case if in source tables 
record is not exist (it was selected default visibility setting), then entity is not written to resolved tables.
For `ProductVisibilityResolved`, entity is not written to resolved table is case then in source table 
(ProductVisibility) exist record with "Config" value.

Each create/update/delete operation in source visibility entities automatically performs 
corresponding change in the resolve tables.

Also each row in cache tables stores one on the data sources:
* STATIC - value is calculated based on selected option and fallback
* CATEGORY - value is calculated based on related category, ID of the category also stored in DB

Here are tables that describe calculation algorithms for all cache values.   

#####Visibility to All
| `ProductToAllVisibilityResolved` | **Category**                                | **Config** | **Hidden**                                  | **Visible**                                 |
|----------------------------------|---------------------------------------------|------------|---------------------------------------------|---------------------------------------------|
| **scope (FK) (PK)**              | Get scope from current product visibility   |      X     | Get scope from current product visibility   | Get scope from current product visibility   |
| **product (FK)**                 | Get product from current product visibility |      X     | Get product from current product visibility | Get product from current product visibility |
| **visibility**                   | Take from category visibility cache         |      X     |                   ::HIDDEN                  |                  ::VISIBLE                  |
| **sourceProductVisibility (FK)** |                   null                      |      X     | Current product visibility                  | Current product visibility                  |
| **source**                       |                ::CATEGORY                   |      X     |                   ::STATIC                  |                   ::STATIC                  |
| **category (FK)**                | Get category from product                   |      X     |                     null                    |                     null                    |

#####Visibility to Account Group
| `ProductToAccountGroupVisibilityResolved`    | **Current Product (Visibility to All)** | **Category**                                               | **Hidden**                                                | **Visible**                                               |
|----------------------------------------------|-----------------------------------------|------------------------------------------------------------|-----------------------------------------------------------|-----------------------------------------------------------|
| **scope (FK) (PK)**                          |                    X                    | Get scope from current account group product visibility    | Get scope from current account group product visibility   | Get scope from currentaccount group product visibility    |
| **accountGroup (FK) (PK)**                   |                    X                    | Get group from current account group product visibility    | Get group from current account group product visibility   | Get group from current account group product visibility   |
| **product (FK) (PK)**                        |                    X                    | Get product from current account group product visibility  | Get product from current account group product visibility | Get product from current account group product visibility |
| **visibility**                               |                    X                    | Take from category visibility cache for this account group |                          ::HIDDEN                         |                         ::VISIBLE                         |
| **sourceProductVisibility (FK)**             |                    X                    | Current account group product visibility                   | Current account group product visibility                  | Current account group product visibility                  |
| **source**                                   |                    X                    |                         ::CATEGORY                         |                          ::STATIC                         |                          ::STATIC                         |
| **category (FK)**                            |                    X                    | Get category from product                                  |                            null                           |                            null                           |

#####Visibility to Account
| `ProductToAccountVisibilityResolved`    | **Account Group** | **Current Product**                                  | **Category**                                         | **Hidden**                                                | **Visible**                                               |
|-----------------------------------------|-------------------|------------------------------------------------------|------------------------------------------------------|-----------------------------------------------------------|-----------------------------------------------------------|
| **scope (FK) (PK)**                     |         X         | Get scope from cur. acc. product visibility          | Get scope from cur. acc. product visibility          | Get scope from cur. acc. product visibility               | Get scope from cur. acc. group product visibility         |
| **account (FK) (PK)**                   |         X         | Get account from cur. acc. product visibility        | Get account from cur. acc. product visibility        | Get account from cur. acc. product visibility             | Get account from cur. acc. product visibility             |
| **product (FK) (PK)**                   |         X         | Get product from cur. acc. product visibility        | Get product fromcur. acc. product visibility         | Get product from cur. acc. product visibility             | Get product from cur. acc. product visibility             |
| **visibility**                          |         X         |            ::VISIBILITY_FALLBACK_TO_ALL              | Take from category visibility cache for this account |                          ::HIDDEN                         |                         ::VISIBLE                         |
| **sourceProductVisibility (FK)**        |         X         | Current account product visibility                   | Current account product visibility                   | Current account product visibility                        | Current account product visibility                        |
| **source**                              |         X         |                    ::STATIC                          |                      ::CATEGORY                      |                          ::STATIC                         |                          ::STATIC                         |
| **category (FK)**                       |         X         |                      null                            |               Get category from product              |                            null                           |                            null                           |


####Cache builders
The above listed steps, with the resolved entities for manipulation with the source entities. 
But what if for example developer changed the product category or even removed it, and it will affect all the caches 
for products that have this category? In this case all level have cache builders.

Cache Builder have been implemented using composite pattern.

There are cache builder classes for each listed levels: `ProductResolvedCacheBuilder`, 
`AccountProductResolvedCacheBuilder`, `AccountGroupProductResolvedCacheBuilder` each of which implements 
`CacheBuilderInterface` (these are leaves).

Composite is `CacheBuilder` class that aggregates all described leaves.
To update the cache for all product visibility levels developer can run command: 
`product:visibility:cache:build`.

To build a cache for all levels of visibility categories, there are also the corresponding cache Builder.

Here is a list of possible cases that require to update the cache:

| **Operation**                                            | **Action**                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
|----------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Updating visibility setting for any product at any level | Updating product visibility cache for that specific visibility (see table above)                                                                                                                                                                                                                                                                                                                                                                                                                   |
| Delete category from product                             | Rebuild product visibility cache (on all levels) for all products in which it was removed, but only where been selected "category" option in source visibility                                                                                                                                                                                                                                                                                                                                     |
| Update category in product                               | Updating product visibility cache for that product where been selected "category" option in source visibility (for all levels)                                                                                                                                                                                                                                                                                                                                                                     |
| Moving category in the tree                              | If visibility setting of that category (for any category visibility level) was selected as "parent category" - Rebuild product visibility cache (on all product visibility levels) for all products which included in this category, but only where been selected "category" option in source visibility. <br> If there is at least one category, the parent of which is this moving category - similar Rebuild product visibility cache for all products which included in all subtree categories.|
| Change category visibility                               | Rebuild product visibility cache (on all levels) for all products which included in this category, but only where been selected "category" option in source visibility                                                                                                                                                                                                                                                                                                                             |


### Visibility Calculation 

All described cache tables contain information about visibility. 
There are following constant options to store this information:

* VISIBLE = 1
* HIDDEN = -1
* VISIBILITY_FALLBACK_TO_CONFIG = 0
* VISIBILITY_FALLBACK_TO_ALL = 2
* null

After all this information is stored in DB visibility calculation can be performed. It uses following formula to 
understand whether product is visible or not:

```
PRODUCT_VISIBILITY + ACCOUNT_GROUP_VISIBILITY * 10 + ACCOUNT_VISIBILITY * 100 > 0
```

So, value on each level might affect result calculation, and higher level is more important. 
If visibility is not defined (null) then zero value is used. The only exceptions are values VISIBILITY_FALLBACK_TO_ALL and VISIBILITY_FALLBACK_TO_CONFIG.
VISIBILITY_FALLBACK_TO_ALL - it used to distinguish fallback from `Account` value to `Visibility to All` value, 
and it means that `Visibility to All` value should be used instead of `Account` value.
VISIBILITY_FALLBACK_TO_CONFIG - it used to distinguish fallback from `Visibility to All`, `Account` or `Account Group` value to category `Config` value,
and it means that Category Visibility value from System Configuration should be used instead of `Visibility to All`, `Account` or `Account Group` value respectively.

For more information about calculation logic see `ProductVisibilityQueryBuilderModifier` class.
