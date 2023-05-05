@regression
@ticket-BB-20791
@ticket-BB-21934
@fixture-OroShoppingListBundle:MyShoppingListsFixture.yml
Feature: Add Product to Customer User's Own Shopping List
  In order not to add product to customer's sharing shopping list
  As an customer user with administrator role
  I should be able to add product to my own new created shopping list rather than a existed customer sharing one.

  Scenario: Create different window session
    Given sessions active:
      | Admin  | first_session  |
      | Buyer  | second_session |
      | Buyer2 | second_session |

  Scenario: Prepare product attributes/ products/ price lists
    Given I proceed as the Admin
    And I login as administrator

    # Import attributes
    And I go to Products / Product Attributes
    And I click "Import file"
    And I upload "configurable_products_for_matrix_forms/products_attributes.csv" file to "Shopping List Import File Field"
    And I click "Import file"
    And I reload the page
    And I confirm schema update

    # Update attribute family
    And I go to Products / Product Families
    And I click Edit Attribute Family in grid
    And set Attribute Groups with:
      | Label           | Visible | Attributes |
      | Attribute group | true    | [Attribute 1, Attribute 2, Attribute 3] |
    And I save form
    Then I should see "Successfully updated" flash message

    # Prepare products
    And I go to Products / Products
    And I click "Import file"
    And I upload "configurable_products_for_matrix_forms/products.csv" file to "Shopping List Import File Field"
    And I click "Import file"

    # Prepare product prices
    And I go to Sales/ Price Lists
    And click view "Default Price List" in grid
    And I click "Import file"
    And I upload "configurable_products_for_matrix_forms/products_prices.csv" file to "Shopping List Import File Field"
    And I click "Import file"

  Scenario: Check the second buyer's shopping list
    Given I proceed as the Buyer2
    And I signed in as AmandaRCole@example.org on the store frontend
    And I type "CC38" in "search"
    And I click "Search Button"
    Then I should see "Add to Shopping List"

  Scenario: Clear the buyer's shopping list at first
    Given I proceed as the Buyer
    And I signed in as NancyJSallee@example.org on the store frontend
    Then I should not see "3 Shopping Lists"
    When I open page with shopping list "Shopping List 2"
    And I click "Shopping List Actions"
    And I click "Delete"
    And I click "Yes, delete"
    Then I should see "Shopping List deleted" flash message

  Scenario: Add a new product to shopping list as the buyer
    Given I type "BB04" in "search"
    And I click "Search Button"
    Then I should not see "Update Shopping List 3"
    When I click "Add to Shopping List"
    And I should see 'Product has been added to "Shopping List"' flash message
    And I follow "Shopping List" link within flash message "Product has been added to \"Shopping List\""
    Then I should see following grid:
      | SKU  | Item      |
      | BB04 | Product 4 |
    And I click "Shopping List Actions"
    And I click "Delete"
    And I click "Yes, delete"
    Then should see "Shopping List deleted" flash message

  Scenario: To add a new product to shopping list in quick order form as the buyer
    Given I click "Quick Order Form"
    Then I should not see "Add to Shopping List 3"
    When I type "BB04" in "SKU1" from "Quick Order Form"
    And I wait for products to load
    And I type "1" in "Quick Order Form > QTY1"
    When I click "Add to Shopping List"
    Then I should see '1 product was added (view shopping list)' flash message
    When I follow "shopping list" link within flash message "1 product was added (view shopping list)"
    Then I should see following grid:
      | SKU  | Item      |
      | BB04 | Product 4 |
    And I click "Shopping List Actions"
    And I click "Delete"
    And I click "Yes, delete"
    Then should see "Shopping List deleted" flash message

  Scenario: Add a set of new product to shopping list by matrix order form as the buyer
    Given I type "CNFB" in "search"
    And I click "Search Button"
    Then I should not see "Add to Shopping List 3"
    When I fill "Matrix Grid Form" with:
      |          | Value 21 |
      | Value 11 | 1        |
      | Value 12 | 1        |
    When I click "Add to Shopping List"
    Then I should see 'Shopping list "Shopping List" was updated successfully' flash message
    When I follow "Shopping List" link within flash message "Shopping list \"Shopping List\" was updated successfully"
    Then I should see following grid:
      | SKU       | Item                                                             | Qty Update All |
      | PROD_B_11 | ConfigurableProductB Attribute 1: Value 11 Attribute 2: Value 21 | 1 item         |
      | PROD_B_21 | ConfigurableProductB Attribute 1: Value 12 Attribute 2: Value 21 | 1 item         |
    And I click "Shopping List Actions"
    And I click "Delete"
    And I click "Yes, delete"
    Then should see "Shopping List deleted" flash message

  Scenario: Administrator sets option "Show All Lists In Shopping List Widgets" to No
    Given I proceed as the Admin
    And I go to System/Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Show All Lists in Shopping List Widgets" field
    And I check "Show All Lists in Shopping List Widgets"
    And save form
    Then I should see "Configuration saved" flash message

  Scenario: Add a new product to shopping list as the second buyer with "Show All Lists In Shopping List Widgets" option on
    Given I proceed as the Buyer
    And I type "BB04" in "search"
    And I click "Search Button"
    Then I should not see "Add to Shopping List"
    When I type "4" in "Product Quantity"
    And I click "Update Shopping List 3"
    Then I should see 'Product has been updated in "Shopping List 3"' flash message
    When I open page with shopping list "Shopping List 3"
    Then I should see following grid:
      | SKU  | Item                               | Qty Update All |
      | BB04 | Configurable Product 1 Note 4 text | 4 item         |

  Scenario: To add a new product to shopping list in quick order form as the buyer with "Show All Lists In Shopping List Widgets" option on
    Given I click "Quick Order Form"
    Then I should see "Add to Shopping List 3"
    When I type "BB04" in "SKU1" from "Quick Order Form"
    And I wait for products to load
    And I type "1" in "Quick Order Form > QTY1"
    And I click "Add to Shopping List 3"
    Then I should see '1 product was added (view shopping list)' flash message
    When I follow "shopping list" link within flash message "1 product was added (view shopping list)"
    Then I should see following grid:
      | SKU  | Item                               | Qty Update All |
      | BB04 | Configurable Product 1 Note 4 text | 5 item         |

  Scenario: Add a set of new product to shopping list by matrix order form as the buyer with "Show All Lists In Shopping List Widgets" option on
    Given I type "CNFB" in "search"
    And I click "Search Button"
    Then I should see "Add to Shopping List 3"
    When I fill "Matrix Grid Form" with:
      |          | Value 21 |
      | Value 11 | 1        |
      | Value 12 | 1        |
    When I click "Shopping List Dropdown"
    And I click "Add to Shopping List 3" in "Shopping List Button Group Menu" element
    Then I should see 'Shopping list "Shopping List 3" was updated successfully' flash message
    When I follow "Shopping List 3" link within flash message "Shopping list \"Shopping List 3\" was updated successfully"
    And I click "Next"
    When I filter SKU as contains "PROD_B"
    Then I should see following grid:
      | SKU       | Item                                                             | Qty Update All |
      | PROD_B_11 | ConfigurableProductB Attribute 1: Value 11 Attribute 2: Value 21 | 1 item         |
      | PROD_B_21 | ConfigurableProductB Attribute 1: Value 12 Attribute 2: Value 21 | 1 item         |
