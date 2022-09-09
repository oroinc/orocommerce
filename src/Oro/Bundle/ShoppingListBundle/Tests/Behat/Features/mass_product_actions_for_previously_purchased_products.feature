@regression
@fixture-OroOrderBundle:previously-purchased.yml
@skip
Feature: Mass Product Actions for Previously purchased products
  In order to add multiple products to a shopping list
  As a Customer User
  I want to have ability to select multiple products and add them to a shopping list from previously purchased products

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Enable previously purchased section
    Given I operate as the Admin
    And I login as administrator
    When I go to System / Configuration
    And I follow "Commerce/Orders/Purchase History" on configuration sidebar
    And fill "Purchase History Settings Form" with:
      | Enable Purchase History Use Default | false |
      | Enable Purchase History             | true  |
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario: "Create New Shopping List" mass action for previously purchased products
    Given I operate as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on homepage
    When I follow "Account"
    And I click "Previously Purchased"
    Then page has "Previously Purchased" header
    And I should see mass action checkbox in row with PSKU1 content for "Product Frontend Grid"
    When I click "Catalog Switcher Toggle"
    And I click "No Image View"
    Then I should see mass action checkbox in row with PSKU1 content for "Product Frontend Grid"
    When I click "Catalog Switcher Toggle"
    And I click "Gallery View"
    And I check PSKU1 record in "Product Frontend Grid" grid
    And I fill line item with "PSKU1" in frontend product grid:
      | Quantity | 10   |
    And I check PSKU2 record in "Product Frontend Grid" grid
    And I fill line item with "PSKU2" in frontend product grid:
      | Quantity | 15   |
    And I scroll to top
    And I click "Create New Shopping List" in "ProductFrontendMassPanelInBottomSticky" element
    Then should see an "Create New Shopping List popup" element
    And type "New Shopping List" in "Shopping List Name"
    When click "Create and Add"
    Then should see 'Shopping list "New Shopping List" was created successfully' flash message
    When I hover on "Shopping Cart"
    And click "New Shopping List"
    Then I should see following line items in "Shopping List Line Items Table":
      | SKU   | Quantity | Unit |
      | PSKU1 | 10       | item |
      | PSKU2 | 15       | item |
