## Category Visibility

Category visibility is a functionality that enables product categories to be visible or hidden for specific customers, customer groups, or websites. Category visibility relates to [product visibility](./product-visibility.md) and affects its settings.

### General Information

Similarly to the product visibility, category visibility has three levels of visibility per each product:

* Visibility to all (`category_visibility`)
* Visibility to customers (`customer_category_visibility`)
* Visibility to customer groups (`customer_group_category_visibility`)

#### Visibility to All

* **Parent Category** - `Default value`. The option inherits the value from the parent category of this product.
* **Config** - The option inherits the value from the `Category Visibility to Customers` parameter in the system configuration. 
* **Hidden** - Provides a specific static value.
* **Visible** - Provides a specific static value.

#### Visibility to Customer Groups

* **Visibility to All**  - `Default value`. The option inherits the configuration set in the Visibility to All field.
* **Parent Category** - Inherits the value from the parent category for the selected customer group.
* **Hidden** - Provides a specific static value.
* **Visible** - Provides a specific static value.

#### Visibility to Customers

* **Customer Group** - `Default value`. The option inherits the visibility configuration of the customer group.
* **Visibility to All** - Inherits the value set for the `Visibility to All` field.
* **Parent Category** - The value is taken from the parent category for the selected customer.
* **Hidden** - Provides a specific static value.
* **Visible** - Provides a specific static value.

There are three corresponding entities in the database for each visibility level:

* `CategoryVisibility`
* `CustomerGroupCategoryVisibility`
* `CustomerCategoryVisibility`

Each of them implements `VisibilityInterface`.

#### Addition Information

* If the default value for a particular category is selected, then the configuration for this category will not be recorded to the database.
* If a category does not have a parent category, then the `Parent Category` option is unavailable for all visibility levels. 

### Category Visibility Cache

Resolved category visibility settings must be pre-calculated and cached for better performance. 
There are 3 resolved entities in the database that contain static visibility values, visible or hidden, and the config value.  
They are `CategoryVisibilityResolved`, `CustomerGroupCategoryVisibilityResolved`, and `CustomerCategoryVisibilityResolved`. Each of them extends the `BaseCategoryVisibilityResolved` abstract class.

If neither the `CustomerCategoryVisibilityResolved` nor the `CustomerGroupCategoryVisibilityResolved` entity record is displayed in the source table, it means that they have been selected as default values. Therefore, the entity configuration is not recorded to the resolved tables.
If the `CategoryVisibilityResolved` entity is not recorded to a resolved table, it means that the record with the `Config` value exists in the source table (`CategoryVisibility`).

Each create/update/delete operation in the source visibility entities automatically performs corresponding changes to the resolved tables.

To force visibility cache building on all 3 levels, a developer runs the `product:visibility:cache:build` command . This command also rebuilds the product visibility cache.

Also, each row in cache tables stores one of the following data sources:
* SOURCE_STATIC - The value is calculated based on selected and fallback options.
* SOURCE_PARENT_CATEGORY - The value is calculated based on the related category and the ID of the category that is also stored in DB.

Here is a list of possible cases that require to update the cache:

#### Category Visibility to All

| `CategoryVisibilityResolved`     | **Parent Category**                          | **Config** | **Hidden**                                    | **Visible**                                  |
|----------------------------------|----------------------------------------------|------------|-----------------------------------------------|----------------------------------------------|
| **category (FK) (PK)**           | Get category from current category visibility|      X     | Get category from current category visibility | Get category from current category visibility|
| **scope (FK) (PK)**              |                                              |      X     |                                               |                                              |
| **sourceCategoryVisibility (FK)**|                   null                       |      X     | Current category visibility                   | Current category visibility                  |
| **visibility**                   | Get parent category visibility from cache    |      X     |             ::VISIBILITY_HIDDEN               |             ::VISIBILITY_VISIBLE             |
| **source**                       |           ::SOURCE_PARENT_CATEGORY           |      X     |               ::SOURCE_STATIC                 |               ::SOURCE_STATIC                |

#### Category Visibility to Customer Group

| `CustomerGroupCategoryVisibilityResolved` | **Visibility to All** | **Parent Category**                                              | **Hidden**                                                 | **Visible**                                                |
|------------------------------------------|-----------------------|------------------------------------------------------------------|------------------------------------------------------------|------------------------------------------------------------|
| **category (FK) (PK)**                   |          X            | Get category from current customer group category visibility      | Get category from current customer group category visibility| Get category from current customer group category visibility|
| **scope (FK) (PK)**                      |          X            |                                                                  |                                                            |                                                            |
| **sourceProductVisibility (FK)**         |          X            | Current customer group category visibility                        | Current customer group product visibility                   | Current customer group category visibility                  |
| **visibility**                           |          X            | Get parent category visibility from cache for this customer group |                     ::VISIBILITY_HIDDEN                    |                   ::VISIBILITY_VISIBLE                     |
| **source**                               |          X            |           ::SOURCE_PARENT_CATEGORY                               |                       ::SOURCE_STATIC                      |                     ::SOURCE_STATIC                        |

#### Category Visibility to Customer
| `CustomerCategoryVisibilityResolved`     | **Customer Group** | **Visibility to All**                                       | **Parent Category**                                        | **Hidden**                                           | **Visible**                                          |
|-----------------------------------------|-------------------|-------------------------------------------------------------|------------------------------------------------------------|------------------------------------------------------|------------------------------------------------------|
| **category (FK) (PK)**                  |         X         | Get category from current customer group category visibility | Get category from current customer category visibility      | Get category from current customer category visibility| Get category from current customer category visibility|
| **scope (FK) (PK)**                     |         X         |                                                             |                                                            |                                                      |                                                      |
| **sourceProductVisibility (FK)**        |         X         | Current customer category visibility                         | Current customer category visibility                        | Current customer product visibility                   | Current customer product visibility                   |
| **visibility**                          |         X         | Get category visibility to all from cache                   | Get parent category visibility from cache for this customer |                   ::VISIBILITY_HIDDEN                |                   ::VISIBILITY_VISIBLE               |
| **source**                              |         X         |                 ::SOURCE_STATIC                             |               ::SOURCE_PARENT_CATEGORY                     |                     ::SOURCE_STATIC                  |                       ::SOURCE_STATIC                |


All described cache tables contain the information about visibility. The information is stored with the help of the following constant options: 
. 
* VISIBILITY_VISIBLE = 1
* VISIBILITY_HIDDEN = -1
* VISIBILITY_FALLBACK_TO_CONFIG = 0
* null

`VISIBILITY_FALLBACK_TO_CONFIG` is used to inherit the value from the `Category Visibility` option set in the system configuration.    
For example:
```
Parent category:
    Category Visibility to All: Config
    Category Visibility to Customer Group 1: Visibility to All
    
Child category:
    Category Visibility to All: Config
    Category Visibility to Customer Group 1: Parent Category
```
In this case, `CustomerGroupCategoryVisibility` for a child category should be:

| **Field**                                | **Value**                                                        |
|------------------------------------------|------------------------------------------------------------------|
| **Id (PK)**                              |             CustomerGroupCategoryVisibilityId                    |
| **category (FK)**                        |                       ChildCategoryId                            |
| **scope (FK)**                           |                       Visibility Scope                           |
| **customer (FK)**                        |                       CustomerGroup1Id                           |
| **visibility**                           |                      ::PARENT_CATEGORY                           |

And `CustomerGroupCategoryVisibilityResolved` for a child category should be:

| **Field**                                | **Value**                                                        |
|------------------------------------------|------------------------------------------------------------------|
| **category (FK) (PK)**                   |                       ChildCategoryId                            |
| **scope (FK) (PK)**                      |                       Scope                                      |
| **sourceProductVisibility (FK)**         |               CustomerGroupCategoryVisibilityId                  |
| **visibility**                           |               ::VISIBILITY_FALLBACK_TO_CONFIG                    |
| **source**                               |                  ::SOURCE_PARENT_CATEGORY                        |

    
#### Cache Builders

The cache builders similar to [product cache builders](./product-visibility.md#cache-builders) enable developers to build category visibility cache. 
To update the cache for all visibility levels, a developer should run the `product:visibility:cache:build` command.
