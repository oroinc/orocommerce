##Product Visibility based on UI Product Visibility Settings

First of all, there are 3 levels of visibility for each product scoped certain website.
On the each product view / edit page there is a visibility setting the following fields:

###Product Visibility (Visibility to All):
* **Category (Visibility to All)** - `Default value`. Value is taken from the category of this product
* **Config** - The value is taken from the global settings
* **Hidden** - Specific static value
* **Visible** - specific static value

###Visibility to Account Groups:
* **Current Product (Visibility to All)** - `Default value`. Fallback on previous level
* **Category** - Value is taken from the category of this product
* **Hidden** - Specific static value
* **Visible** - Specific static value

###Visibility to Accounts:
* **Account Group (Visibility to this Account's Group)** - `Default value`. Fallback on previous level
* **Current Product (Visibility to All)**  - Fallback to *"Visibility to All"* level
* **Category (Visibility to this Account)** - Value is taken from the category of this product
* **Hidden** - Specific static value
* **Visible** - Specific static value

There is entities in database for each listed levels:
`ProductVisibility`, `AccountProductVisibility`, `AccountGroupProductVisibility` each of which implement `VisibilityInterface` and `WebsiteAwareInterface` interfaces.

###Addition information:
* If any level is selected by default - the entity is not written to the database.
* If product hasn't category, then "Category" option is not available for all levels. If visibility setting already exist with "category" value, and category is deleted for specific product, then setting value is changed to default value (for visibility to account and visibility to account group), for visibility to all that value changed to "config".     
* If account hasn't account group, then "Account Group" option is not available for Visibility to Accounts.  If visibility setting already exist with "Account Group" value, and account group is deleted for specific account, then setting value is changed to "Current Product".

##Cache for Product Visibility
Resolved visibility settings must be pre-calculated and cached for greater performance. To do this for each of the levels create resolved entity, which contains only static visibility values (visible or hidden):

There is resolved entities in database for each listed levels:
`ProductVisibilityResolved`, `AccountProductVisibilityResolved`, `AccountGroupProductVisibilityResolved` each of which extend `BaseProductVisibilityResolved` abstract class

For `AccountProductVisibilityResolved` and `AccountGroupProductVisibilityResolved` in case then in source tables record is not exist (it was selected default visibility setting), then entity is not written to resolved tables.
For `ProductVisibilityResolved`, entity is not written to resolved table is case then in source table (ProductVisibility) exist record with "config" value.

For any create/update/delete in source visibility entities must be accompanied by a corresponding change in the resolve tables.

####Visibility to All
| `ProductToAllVisibilityResolved` | **Category**                            | **Config** | **Hidden**                                  | **Visible**                                 |
|----------------------------------|-----------------------------------------|------------|---------------------------------------------|---------------------------------------------|
| **website (FK) (PK)**            | Get website from old product visibility |      X     | Get website from current product visibility | Get website from current product visibility |
| **product (FK)**                 | Get product from old product visibility |      X     | Get product from current product visibility | Get product from current product visibility |
| **visibility**                   | Take from category visibility cache     |      X     |                   ::HIDDEN                  |                  ::VISIBLE                  |
| **sourceProductVisibility (FK)** |                   null                  |      X     | Current product visibility                  | Current product visibility                  |
| **source**                       |                ::CATEGORY               |      X     |                   ::STATIC                  |                   ::STATIC                  |
| **category (FK)**                | Get category from product               |      X     |                     null                    |                     null                    |


####Visibility to Account Group
| `ProductToAccountGroup` `VisibilityResolved` | **Current Product (Visibility to All)** | **Category**                                               | **Hidden**                                                | **Visible**                                               |
|----------------------------------------------|-----------------------------------------|------------------------------------------------------------|-----------------------------------------------------------|-----------------------------------------------------------|
| **website (FK) (PK)**                        |                    X                    | Get website from current account group product visibility  | Get website from currentaccount group product visibility  | Get website from currentaccount group product visibility  |
| **accountGroup (FK) (PK)**                   |                    X                    | Get group from current account group product visibility    | Get group from current account group product visibility   | Get group from current account group product visibility   |
| **product (FK) (PK)**                        |                    X                    | Get product from current account group product visibility  | Get product from current account group product visibility | Get product from current account group product visibility |
| **visibility**                               |                    X                    | Take from category visibility cache for this account group |                          ::HIDDEN                         |                         ::VISIBLE                         |
| **sourceProductVisibility (FK)**             |                    X                    | Current account group product visibility                   | Current account group product visibility                  | Current account group product visibility                  |
| **source**                                   |                    X                    |                         ::CATEGORY                         |                          ::STATIC                         |                          ::STATIC                         |
| **category (FK)**                            |                    X                    | Get category from product                                  |                            null                           |                            null                           |


####Visibility to Account
| `ProductToAccount` `VisibilityResolved` | **Account Group** | **Current Product**                                                                               | **Category**                                         | **Hidden**                                                | **Visible**                                               |
|-----------------------------------------|-------------------|---------------------------------------------------------------------------------------------------|------------------------------------------------------|-----------------------------------------------------------|-----------------------------------------------------------|
| **website (FK) (PK)**                   |         X         | Get website from cur. acc. product visibility                                                     | Get website from cur. acc. product visibility        | Get website from cur. acc. product visibility             | Get website from cur. acc. group product visibility       |
| **account (FK) (PK)**                   |         X         | Get account from cur. acc. product visibility                                                     | Get account from cur. acc. product visibility        | Get account from cur. acc. product visibility             | Get account from cur. acc. product visibility             |
| **product (FK) (PK)**                   |         X         | Get product from cur. acc. product visibility                                                     | Get product fromcur. acc. product visibility         | Get product from cur. acc. product visibility             | Get product from cur. acc. product visibility             |
| **visibility**                          |         X         | Find by product in ProductToAllVisibilityResolved and take visibility. If null - take from config | Take from category visibility cache for this account |                          ::HIDDEN                         |                         ::VISIBLE                         |
| **sourceProductVisibility (FK)**        |         X         | Current account product visibility                                                                | Current account product visibility                   | Current account product visibility                        | Current account product visibility                        |
| **source**                              |         X         |                                              ::STATIC                                             |                      ::CATEGORY                      |                          ::STATIC                         |                          ::STATIC                         |
| **category (FK)**                       |         X         |                                                null                                               |               Get category from product              |                            null                           |                            null                           |

