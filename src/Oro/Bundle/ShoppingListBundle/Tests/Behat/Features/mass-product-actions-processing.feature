@fixture-OroCatalogBundle:all_products_page.yml
Feature: Mass Product Actions

  In order to add multiple products to a shopping list
  As a Customer User or Guest
  I want to have ability to select multiple products and add them to a shopping list

  Scenario: Create different window session
    Given sessions active:
      | Admin  |first_session |
      | User   |second_session|

  Scenario: Administrator enables all products feature
    Given I proceed as the Admin
    And login as administrator
    And I go to System/ Configuration
    And follow "Commerce/Catalog/Special Pages" on configuration sidebar
    And uncheck "Use default" for "Enable all products page" field
    And I check "Enable all products page"
    And save form
    Then I should see "Configuration saved" flash message

  Scenario: I add link to all products page to the main menu
    Given I proceed as the Admin
    When I go to System/ Frontend Menus
    And I click view "commerce_main_menu" in grid
    And I click "Create Menu Item"
    And I fill "Commerce Menu Form" with:
      | Title  | All Products         |
      | URI    | /catalog/allproducts |
    And save form
    Then I should see "Menu item saved successfully" flash message

  Scenario: "Create New shopping list" mass action on the category list view
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "All Products"
    When I click "No Image View"
    And I check PSKU1 record in "Product Frontend Grid" grid
    And I fill line item with "PSKU1" in frontend product grid:
      | Quantity | 10   |
      | Unit     | item |
    And I check PSKU2 record in "Product Frontend Grid" grid
    And I fill line item with "PSKU2" in frontend product grid:
      | Quantity | 15   |
      | Unit     | item |
    And I click "Create New Shopping List" link from mass action dropdown in "Product Frontend Grid"
    Then should see an "Create New Shopping List popup" element
    And type "New Shopping List" in "Shopping List Name"
    And click "Create and Add"
    Then should see 'Shopping list "New Shopping List" was created successfully' flash message
    When I hover on "Shopping Cart"
    And click "New Shopping List"
    Then I should see following line items in "Shopping List Line Items Table":
      | SKU   | Quantity | Unit |
      | PSKU1 | 10       | item |
      | PSKU2 | 15       | item |

  Scenario: "Add to current shopping list" mass action on the category list view
    Given I proceed as the User
    And I click "All Products"
    And I check PSKU3 record in "Product Frontend Grid" grid
    And I fill line item with "PSKU3" in frontend product grid:
      | Quantity | 7   |
      | Unit     | item |
    And I click "Add to current Shopping List" link from mass action dropdown in "Product Frontend Grid"
    Then I should see "1 product was added" flash message
    When I hover on "Shopping Cart"
    And click "New Shopping List"
    Then I should see following line items in "Shopping List Line Items Table":
      | SKU   | Quantity | Unit |
      | PSKU1 | 10       | item |
      | PSKU2 | 15       | item |
      | PSKU3 | 7        | item |

  #Todo: replace after #BAP-15242 done, merge scenarios
  @skipWait
  Scenario: Check warning message when products are selected and trying to refresh the page
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "All Products"
    When I check PSKU2 record in "Product Frontend Grid" grid
    And I click "List View"
    And I accept alert
    Then I should see PSKU2 unchecked record in "Product Frontend Grid"
