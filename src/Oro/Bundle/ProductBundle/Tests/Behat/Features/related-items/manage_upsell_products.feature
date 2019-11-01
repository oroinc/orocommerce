@regression
@fixture-OroProductBundle:related_items_products.yml
@fixture-OroProductBundle:related_items_system_users.yml
@feature-BB-8377
  
Feature: Manage up-sell products
  In order to be able to offer the customer to buy some products instead of the one that he is looking at
  As an Administrator
  I want to manage which products should be considered "Up-sell Items" to the one I am managing

  Scenario: Check if datagrid of a product doesn't contain this product
    Given sessions active:
      | Admin   | first_session  |
      | Manager | second_session |
    And I proceed as the Admin
    Given I login as administrator
    When go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I choose "Up-sell Products" tab
    And I click "Select up-sell products"
    And I filter SKU as contains "PSKU" in "SelectUpsellProductsGrid"
    Then I should see following "SelectUpsellProductsGrid" grid:
      | SKU   | NAME               |
      | PSKU5 | Product5(disabled) |
      | PSKU4 | Product 4          |
      | PSKU3 | Product 3          |
      | PSKU2 | Product 2          |
    And I click "Cancel" in modal window

  Scenario: Create relation
    Given go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I choose "Up-sell Products" tab
    And I click "Select up-sell products"
    And I should see following "SelectUpsellProductsGrid" grid:
      | Is Related | SKU   | NAME               |
      | 0          | PSKU5 | Product5(disabled) |
      | 0          | PSKU4 | Product 4          |
      | 0          | PSKU3 | Product 3          |
      | 0          | PSKU2 | Product 2          |
    When I select following records in "SelectUpsellProductsGrid" grid:
      | PSKU2 |
      | PSKU3 |
    And I click "Select products"
    And I filter SKU as contains "PSKU" in "UpsellProductsEditGrid"
    And I should see following "UpsellProductsEditGrid" grid:
      | SKU   | NAME      |
      | PSKU3 | Product 3 |
      | PSKU2 | Product 2 |
    And I save and close form
    Then I should see "Product has been saved" flash message
    And I choose "Up-sell Products" tab
    And I should see following "UpsellProductsViewGrid" grid:
      | SKU   | NAME      |
      | PSKU3 | Product 3 |
      | PSKU2 | Product 2 |

  Scenario: Grid in popup should have up-sell products checked
    Given go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I choose "Up-sell Products" tab
    And I should see following grid:
      | SKU   | NAME      |
      | PSKU3 | Product 3 |
      | PSKU2 | Product 2 |
    When I click "Select up-sell products"
    Then I should see following "SelectUpsellProductsGrid" grid:
      | Is Related | SKU   | NAME               |
      | 1          | PSKU3 | Product 3          |
      | 1          | PSKU2 | Product 2          |
      | 0          | PSKU5 | Product5(disabled) |
      | 0          | PSKU4 | Product 4          |
    And I click "Cancel" in modal window

  Scenario: Change relation with quick edit
    Given go to Products/ Products
    And I click View PSKU1 in grid
    And I click "Quick edit"
    And I choose "Up-sell Products" tab
    And I click "Select up-sell products"
    When I select following records in "SelectUpsellProductsGrid" grid:
      | PSKU4 |
    And I click "Select products"
    And I click "Delete" on row "PSKU2" in grid
    And I save and close form
    And I choose "Up-sell Products" tab
    Then I should see following "UpsellProductsViewGrid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
      | PSKU3 | Product 3 |

  Scenario: Canceling edit will not affect up-sell products
    Given go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I choose "Up-sell Products" tab
    And I should see following "UpsellProductsEditGrid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
      | PSKU3 | Product 3 |
    And I click "Delete" on row "PSKU4" in grid
    And I click "Cancel"
    And I click View PSKU1 in grid
    And I choose "Up-sell Products" tab
    Then I should see following "UpsellProductsViewGrid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
      | PSKU3 | Product 3 |

  Scenario: Related items should not be visible on view if user has no permission
    Given user has following permissions
      | Assign | Product | None   |
      | Create | Product | Global |
      | Delete | Product | Global |
      | Edit   | Product | Global |
    Then I proceed as the Manager
    And I login as "CatalogManager1" user
    When I go to Products/ Products
    And I click View "PSKU1" in grid
    Then I should not see "Related Items"

  Scenario: Verify edit up-sell products permission will not affect product creation
    When I go to Products/ Products
    And I click "Create Product"
    And I click "Continue"
    And fill "ProductForm" with:
      | SKU    | product12 |
      | Name   | product12 |
      | Status | Enabled   |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Related items should not be visible in product edition if user has no permission
    When I go to Products/ Products
    And I click Edit "PSKU1" in grid
    Then I should not see "Related Items"

  Scenario: Related items should be visible on view if user has at least one permission to any of Related Items
    Given user has following entity permissions enabled
      | Edit Up-Sell Products |
    When I go to Products/ Products
    And I click View "PSKU1" in grid
    Then I should see "Related Items"
    And I should see "UpsellProductsViewGrid" grid

  Scenario: Related items should be visible on edit if user has at least one permission to any of Related Items
    Given I go to Products/ Products
    When I click Edit "PSKU1" in grid
    Then I should see "Related Items"
    And I should see "UpsellProductsEditGrid" grid

  Scenario: Disable up-sell products functionality
    Given I proceed as the Admin
    When go to System/ Configuration
    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
    And I fill "UpsellProductsConfig" with:
      | Enable Up-sell Products Use Default | false |
      | Enable Up-sell Products             | false |
    And I click "Save settings"
    Then go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I should not see "Up-sell Products"
    And I should not see "UpsellProductsViewGrid" grid

  Scenario: Limit should be restricted
    Given go to System/ Configuration
    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
    And I fill "UpsellProductsConfig" with:
      | Enable Up-sell Products                      | true  |
      | Maximum Number Of Assigned Items Use Default | false |
      | Maximum Number Of Assigned Items             | 2     |
    And I click "Save settings"
    When go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I choose "Up-sell Products" tab
    And I click "Select up-sell products"
    And I select following records in "SelectUpsellProductsGrid" grid:
      | PSKU2 |
      | PSKU3 |
      | PSKU4 |
    Then "Select products" button is disabled
    And I should see "Limit of related items has been reached"
    And I click "Cancel" in modal window

  Scenario: Check up-sell grid view after up-sell product title has been updated
    Given go to System/ Configuration
    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
    And I fill "UpsellProductsConfig" with:
      | Enable Up-sell Products                      | true  |
      | Maximum Number Of Assigned Items Use Default | false |
      | Maximum Number Of Assigned Items             | 25    |
    And I click "Save settings"
    And go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I choose "Up-sell Products" tab
    And I click "Select up-sell products"
    And I select following records in "SelectUpsellProductsGrid" grid:
      | PSKU2 |
    And I click "Select products"
    And I save and close form
    And I should see "Product has been saved" flash message
    And I choose "Up-sell Products" tab
    And I should see following "UpsellProductsViewGrid" grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
      | PSKU3 | Product 3 |
      | PSKU2 | Product 2 |
    When go to Products/ Products
    And I click Edit PSKU2 in grid
    And I fill "ProductForm" with:
      | SKU  | PSKU22            |
      | Name | Product 2 updated |
    And I save and close form
    Then go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I choose "Up-sell Products" tab
    And I should see following "UpsellProductsEditGrid" grid:
      | SKU    | NAME              |
      | PSKU4  | Product 4         |
      | PSKU3  | Product 3         |
      | PSKU22 | Product 2 updated |

  Scenario: Check if relation is saved after disable/enable up-sell items feature
    Given go to System/ Configuration
    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
    And I fill "UpsellProductsConfig" with:
      | Enable Up-sell Products Use Default | false |
      | Enable Up-sell Products             | false |
    And I click "Save settings"
    And go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I should not see "Up-sell Products"
    And I should not see "UpsellProductsViewGrid" grid
    When go to System/ Configuration
    And I follow "Commerce/Catalog/Related Items" on configuration sidebar
    And I fill "UpsellProductsConfig" with:
      | Enable Up-sell Products Use Default | false |
      | Enable Up-sell Products             | true  |
    And I click "Save settings"
    Then go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I choose "Up-sell Products" tab
    And I should see following grid:
      | SKU    | NAME              |
      | PSKU4  | Product 4         |
      | PSKU3  | Product 3         |
      | PSKU22 | Product 2 updated |

  Scenario: Verify relation is removed in case when up-sell product has been removed
    Given go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I choose "Up-sell Products" tab
    And I click "Select up-sell products"
    And I select following records in "SelectUpsellProductsGrid" grid:
      | PSKU3 |
    And I click "Select products"
    And I save and close form
    And I should see "Product has been saved" flash message
    And I choose "Up-sell Products" tab
    And I should see following "UpsellProductsViewGrid" grid:
      | SKU    | NAME              |
      | PSKU4  | Product 4         |
      | PSKU3  | Product 3         |
      | PSKU22 | Product 2 updated |
    And go to Products/ Products
    And I click Edit "PSKU22" in grid
    When I click "Delete"
    And I confirm deletion
    Then go to Products/ Products
    And I click Edit "PSKU1" in grid
    And I choose "Up-sell Products" tab
    And I should see following grid:
      | SKU   | NAME      |
      | PSKU4 | Product 4 |
      | PSKU3 | Product 3 |
