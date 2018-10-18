## Product Visibility

Product visibility is a functionality that enables products to be visible or hidden  for specific customers, customer groups, and websites. 

### General Information

There are 3 type levels of visibility per each product: product_visibility, customer_product_visibility, customer_group_product_visibility.
On the product visibility page, you can set the following values:

#### Product Visibility (Visibility to All)

* **Category (Visibility to All)** - `Default value`. The option inherits the value from the category of this product.
* **Config** - The option inherits the value from the "Product Visibility to Customers" parameter in the system configuration.  
* **Hidden** - provides a specific static value.
* **Visible** - provides a specific static value.

#### Visibility to Customer Groups

* **Current Product (Visibility to All)** - `Default value`. The option inherits the configuration set in the Product Visibility (Visibility to All) field.
* **Category** - inherits the value from the category of this product for the selected customer group.
* **Hidden** - provides a specific static value.
* **Visible** - provides a specific static value.

#### Visibility to Customers

* **Customer Group (Visibility to this Customer's Group)** - `Default value`. The option inherits the value set in the Visibility to Customer Groups field.
* **Current Product (Visibility to All)**  - falls back to the Product Visibility (Visibility to All) value.
* **Category (Visibility to this Customer)** - inherits the configuration from the product category for the selected customer.
* **Hidden** - provides a specific static value.
* **Visible** - provides a specific static value.

There are three corresponding entities in the database for each visibility level:
`ProductVisibility`, `CustomerProductVisibility`, and `CustomerGroupProductVisibility`. All of them implement `VisibilityInterface` and `ScopeAwareInterface`.

#### Additional Information

* If the default value for a particular product is selected, then its configuration will not be recorded to the database.
* If a product doesn't have a category, then the "Category" option is unavailable for all visibility levels. 
If the "Category" option has a certain visibility configuration, although the category itself is deleted for the selected product, then the visibility values for the selected customer and the customer group are changed to the default value while the  Visibility to All option value is changed to "Config".  

### Product Visibility Cache

Resolved visibility settings must be pre-calculated and cached for better performance. 

There are 3 resolved entities in the database that contain only static visibility values (visible or hidden). They are `ProductVisibilityResolved`, `CustomerProductVisibilityResolved`, and `CustomerGroupProductVisibilityResolved`. Each entity extends the `BaseProductVisibilityResolved` abstract class.

If neither the `CustomerProductVisibilityResolved` nor the `CustomerGroupProductVisibilityResolved` entity record is displayed in the source table, it means that they have been selected as default values. Therefore, the entity configuration is not recorded to the resolved tables.
If the `ProductVisibilityResolved` entity is not recorded to a resolved table, it means that the record with the "Config" value exists in the source table (ProductVisibility).

Each create/update/delete operation in the source visibility entities automatically performs corresponding changes in the resolve tables.

Also, each row in cache tables stores one of the following data sources:
* STATIC - the value is calculated based on the selected and fallback options;
* CATEGORY - the value is calculated based on the related category and the ID of the category that is also stored in DB.

Here are the tables that describe a calculation algorithm for all cache values.   

#### Visibility to All

| `ProductToAllVisibilityResolved` | **Category**                                  | **Config** | **Hidden**                                    | **Visible**                                 |
|----------------------------------|-----------------------------------------------|------------|-----------------------------------------------|---------------------------------------------|
| **scope (FK) (PK)**              | Get a scope from current product visibility   |      X     | Get a scope from current product visibility   | Get a scope from current product visibility   |
| **product (FK)**                 | Get a product from current product visibility |      X     | Get a product from current product visibility | Get a product from current product visibility |
| **visibility**                   | Take from category visibility cache           |      X     |                   ::HIDDEN                    |                  ::VISIBLE                  |
| **sourceProductVisibility (FK)** |                   null                        |      X     | Current product visibility                    | Current product visibility                  |
| **source**                       |                ::CATEGORY                     |      X     |                   ::STATIC                    |                   ::STATIC                  |
| **category (FK)**                | Get a category from a product                 |      X     |                     null                      |                     null                    |

#### Visibility to Customer Group

| `ProductToCustomerGroupVisibilityResolved`    | **Current Product (Visibility to All)** | **Category**                                               | **Hidden**                                                | **Visible**                                               |
|----------------------------------------------|-----------------------------------------|-------------------------------------------------------------|-----------------------------------------------------------|-----------------------------------------------------------|
| **scope (FK) (PK)**                          |                    X                    | Get a scope from current customer group product visibility  | Get a scope from current customer group product visibility   | Get a scope from current customer group product visibility   |
| **customerGroup (FK) (PK)**                   |                    X                   | Get a group from current customer group product visibility  | Get a group from current customer group product visibility   | Get a group from current customer group product visibility   |
| **product (FK) (PK)**                        |                    X                    | Get a product from current customer group product visibility| Get a product from current customer group product visibility   | Get a product from current customer group product visibility |
| **visibility**                               |                    X                    | Take from category visibility cache for this customer group |                          ::HIDDEN                                 |          ::VISIBLE                                           |
| **sourceProductVisibility (FK)**             |                    X                    | Current customer group product visibility                   | Current customer group product visibility                    | Current customer group product visibility                    |
| **source**                                   |                    X                    |                         ::CATEGORY                          |                          ::STATIC                                 |                          ::STATIC                            |
| **category (FK)**                            |                    X                    | Get a category from a product                               |                            null                                   |                            null                              |

#### Visibility to Customer

| `ProductToCustomerVisibilityResolved`    | **Customer Group** | **Current Product**                                  | **Category**                                         | **Hidden**                                                | **Visible**                                               |
|-----------------------------------------|-------------------|------------------------------------------------------|------------------------------------------------------|-----------------------------------------------------------|-----------------------------------------------------------|
| **scope (FK) (PK)**                     |         X         | Get scope from cur. acc. product visibility          | Get scope from cur. acc. product visibility          | Get scope from cur. acc. product visibility               | Get scope from cur. acc. group product visibility         |
| **customer (FK) (PK)**                   |         X         | Get customer from cur. acc. product visibility        | Get customer from cur. acc. product visibility        | Get customer from cur. acc. product visibility             | Get customer from cur. acc. product visibility             |
| **product (FK) (PK)**                   |         X         | Get product from cur. acc. product visibility        | Get product fromcur. acc. product visibility         | Get product from cur. acc. product visibility             | Get product from cur. acc. product visibility             |
| **visibility**                          |         X         |            ::VISIBILITY_FALLBACK_TO_ALL              | Take from category visibility cache for this customer |                          ::HIDDEN                         |                         ::VISIBLE                         |
| **sourceProductVisibility (FK)**        |         X         | Current customer product visibility                   | Current customer product visibility                   | Current customer product visibility                        | Current customer product visibility                        |
| **source**                              |         X         |                    ::STATIC                          |                      ::CATEGORY                      |                          ::STATIC                         |                          ::STATIC                         |
| **category (FK)**                       |         X         |                      null                            |               Get category from product              |                            null                           |                            null                           |


#### Cache Builders

Cache builders are provided for all visibility levels to avoid affecting the cache of products of a particular category that has been changed or removed.
Cache builders are implemented using a composite pattern.

There are three cache builder classes for each listed level: `ProductResolvedCacheBuilder`, 
`CustomerProductResolvedCacheBuilder`, and `CustomerGroupProductResolvedCacheBuilder`. Each of them implements `CacheBuilderInterface`. These cache builder classes are called leaves.

Composite is the `CacheBuilder` class that aggregates all the mentioned leaves.
To update the cache for all product visibility levels, a developer should run a command: 
`product:visibility:cache:build`.

To build a cache for all levels of visibility categories, the following cache builders are provided.

Here is a list of possible cases that require to update the cache:

| **Operation**                                            | **Action**                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
|----------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Updating visibility settings for any product at any level | Updating product visibility cache for specific visibility (see table above)                                                                                                                                                                                                                                                                                                                                                                                                                   |
| Delete a category from a product                             | Rebuild product visibility cache (at all levels) for all the products where it was removed. It concerns only the products with the "category" option selected in source visibility                                                                                                                                                                                                                                                                                                                                     |
| Update a category in a product                               | Updating product visibility cache for the product with the "category" option selected in source visibility (for all levels)                                                                                                                                                                                                                                                                                                                                                                     |
| Moving a category in the tree                              | If the visibility value of the category (for any category visibility level) was selected as "parent category", you should rebuild product visibility cache (on all product visibility levels) for all products which are included in this category. It concerns only the products with the "category" option selected in source visibility. <br> If there is at least one category which is a child of this moving category, you should also rebuild product visibility cache for all products which are included in all subtree categories.|
| Change category visibility                               | Rebuild product visibility cache (on all levels) for all products which are included in this category, but only if the "category" option is selected in source visibility                                                                                                                                                                                                                                                                                                                             |


### Visibility Calculation 

All described cache tables contain information about visibility that is stored with the help of the following options. 

* VISIBLE = 1
* HIDDEN = -1
* VISIBILITY_FALLBACK_TO_CONFIG = 0
* VISIBILITY_FALLBACK_TO_ALL = 2
* null

Once all the information is stored in DB, visibility calculation can be performed. It uses the following formula to 
check whether a product is visible or not:

```
PRODUCT_VISIBILITY + ACCOUNT_GROUP_VISIBILITY * 10 + ACCOUNT_VISIBILITY * 100 > 0
```

So, a value set on each level affects the calculation result. The higher the level is, the more important it is. 
If visibility is not defined (null) then zero value is used. The only exceptions are the VISIBILITY_FALLBACK_TO_ALL and the VISIBILITY_FALLBACK_TO_CONFIG values.
VISIBILITY_FALLBACK_TO_ALL is used to enable the `Customer` value to fall back to the `Visibility to All` value. 
VISIBILITY_FALLBACK_TO_CONFIG inherits the category visibility value set in the system configuration. It means that the `Config` value is to be used instead of the `Visibility to All`, `Customer`, or `Customer Group` values respectively.

For more information about calculation logic, refer to the `ProductVisibilityQueryBuilderModifier` class.
