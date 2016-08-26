Category visibility
-------------------

Category visibility is a functionality that allows to show or hide some products specific category based on settings parent category
account or account group. Category visibility related to the product visibility and affects [product visibility](./product-visibility.md).

### General Information
As product visibility category visibility has same 3 levels.

####Visibility to all:
* **Parent Category** - `Default value`. Value is taken from the parent category
* **Config** - The value is taken from the system configuration parameter "Category Visibility to Accounts"
* **Hidden** - Specific static value
* **Visible** - Specific static value

####Visibility to Account Groups:
* **Visibility to All**  - `Default value`. Fallback to Visibility to All value
* **Parent Category** - Value is taken from the parent category for selected account group
* **Hidden** - Specific static value
* **Visible** - Specific static value

####Visibility to Accounts:
* **Account Group** - `Default value`. Fallback to Visibility to Account Groups
* **Visibility to All** - Fallback to Visibility to All value
* **Parent Category** - Value is taken from the parent category for selected account
* **Hidden** - Specific static value
* **Visible** - Specific static value

As well as for the product visibility there are entities in database for each listed level:
`CategoryVisibility`, `AccountGroupCategoryVisibility`, `AccountCategoryVisibility` each of which implements 
`VisibilityInterface` interface.

Category visibilities settings do not depend on websites - they are the same for all websites.

####Addition information:

* If default value is selected then the entity is not written to the database.
* If category doesn't have parent category then "Parent Category" option is not available for all levels. 

### Category Visibility Cache

Resolved category category visibility settings must be pre-calculated and cached for greater performance. 
To do this there are 3 resolved entities that contains only static visibility values (visible or hidden) or fallback to config value.

There are resolved entities in database for each level:
`CategoryVisibilityResolved`, `AccountGroupCategoryVisibilityResolved`, `AccountCategoryVisibilityResolved`
each of which extend `BaseCategoryVisibilityResolved` abstract class.

For `AccountCategoryVisibilityResolved`, `AccountGroupCategoryVisibilityResolved` in case if source tables does not
contain a record (default visibility setting was selected), then entity is not written to resolved tables.
For `CategoryVisibilityResolved` entity is not written to resolved table is case then source table 
(CategoryVisibility) contains record with "Config" value.

Each create/update/delete operation in source visibility entities automatically performs 
corresponding change to the resolved tables.

For forced visibility cache building on all 3 levels developer can run command: 
`product:visibility:cache:build`. This command also rebuilds product visibility cache.

Also each row in cache tables stores one on the data sources:
* SOURCE_STATIC - value is calculated based on selected option and fallback
* SOURCE_PARENT_CATEGORY - value is calculated based on related category, ID of the category also stored in DB

Here is a list of possible cases that require to update the cache:

#####Category Visibility to All
| `CategoryVisibilityResolved`     | **Parent Category**                          | **Config** | **Hidden**                                    | **Visible**                                  |
|----------------------------------|----------------------------------------------|------------|-----------------------------------------------|----------------------------------------------|
| **category (FK) (PK)**           | Get category from current category visibility|      X     | Get category from current category visibility | Get category from current category visibility|
| **sourceCategoryVisibility (FK)**|                   null                       |      X     | Current category visibility                   | Current category visibility                  |
| **visibility**                   | Get parent category visibility from cache    |      X     |             ::VISIBILITY_HIDDEN               |             ::VISIBILITY_VISIBLE             |
| **source**                       |           ::SOURCE_PARENT_CATEGORY           |      X     |               ::SOURCE_STATIC                 |               ::SOURCE_STATIC                |

#####Category Visibility to Account Group
| `AccountGroupCategoryVisibilityResolved` | **Visibility to All** | **Parent Category**                                              | **Hidden**                                                 | **Visible**                                                |
|------------------------------------------|-----------------------|------------------------------------------------------------------|------------------------------------------------------------|------------------------------------------------------------|
| **accountGroup (FK) (PK)**               |          X            | Get group from current account group category visibility         | Get group from current account group category visibility   | Get group from current account group category visibility   |
| **category (FK) (PK)**                   |          X            | Get category from current account group category visibility      | Get category from current account group category visibility| Get category from current account group category visibility|
| **sourceProductVisibility (FK)**         |          X            | Current account group category visibility                        | Current account group product visibility                   | Current account group category visibility                  |
| **visibility**                           |          X            | Get parent category visibility from cache for this account group |                     ::VISIBILITY_HIDDEN                    |                   ::VISIBILITY_VISIBLE                     |
| **source**                               |          X            |           ::SOURCE_PARENT_CATEGORY                               |                       ::SOURCE_STATIC                      |                     ::SOURCE_STATIC                        |

#####Category Visibility to Account
| `AccountCategoryVisibilityResolved`     | **Account Group** | **Visibility to All**                                       | **Parent Category**                                        | **Hidden**                                           | **Visible**                                          |
|-----------------------------------------|-------------------|-------------------------------------------------------------|------------------------------------------------------------|------------------------------------------------------|------------------------------------------------------|
| **account (FK) (PK)**                   |         X         | Get account from current account group category visibility  | Get account from current account category visibility       | Get account from current account category visibility | Get account from current account category visibility |
| **category (FK) (PK)**                  |         X         | Get category from current account group category visibility | Get category from current account category visibility      | Get category from current account category visibility| Get category from current account category visibility|
| **sourceProductVisibility (FK)**        |         X         | Current account category visibility                         | Current account category visibility                        | Current account product visibility                   | Current account product visibility                   |
| **visibility**                          |         X         | Get category visibility to all from cache                   | Get parent category visibility from cache for this account |                   ::VISIBILITY_HIDDEN                |                   ::VISIBILITY_VISIBLE               |
| **source**                              |         X         |                 ::SOURCE_STATIC                             |               ::SOURCE_PARENT_CATEGORY                     |                     ::SOURCE_STATIC                  |                       ::SOURCE_STATIC                |


All described cache tables contain information about visibility. 
There are following constant options to store this information:

* VISIBILITY_VISIBLE = 1
* VISIBILITY_HIDDEN = -1
* VISIBILITY_FALLBACK_TO_CONFIG = 0
* null

`VISIBILITY_FALLBACK_TO_CONFIG` is used if value referred to Category Visibility from System Configuration.    
For example:
```
Parent category:
    Category Visibility to All: Config
    Category Visibility to Account Group 1: Visibility to All
    
Child category:
    Category Visibility to All: Config
    Category Visibility to Account Group 1: Parent Category
```
In this case `AccountGroupCategoryVisibility` for child category should be:

| **Field**                                | **Value**                                                        |
|------------------------------------------|------------------------------------------------------------------|
| **Id (PK)**                              |             AccountGroupCategoryVisibilityId                     |
| **category (FK) (PK)**                   |                       ChildCategoryId                            |
| **account (FK)  (PK)**                   |                       AccountGroup1Id                            |
| **visibility**                           |                      ::PARENT_CATEGORY                           |

And `AccountGroupCategoryVisibilityResolved` for child category should be:

| **Field**                                | **Value**                                                        |
|------------------------------------------|------------------------------------------------------------------|
| **accountGroup (FK) (PK)**               |                       AccountGroup1Id                            |
| **category (FK) (PK)**                   |                       ChildCategoryId                            |
| **sourceProductVisibility (FK)**         |               AccountGroupCategoryVisibilityId                   |
| **visibility**                           |               ::VISIBILITY_FALLBACK_TO_CONFIG                    |
| **source**                               |                  ::SOURCE_PARENT_CATEGORY                        |

    
####Cache builders
To build category visibility cache there are cache builders similar to [product cache builders](./product-visibility.md#cache-builders).
To update the cache for all visibility levels developer should run command `product:visibility:cache:build`.
