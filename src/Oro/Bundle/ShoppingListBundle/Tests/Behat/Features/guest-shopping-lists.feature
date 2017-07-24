@ticket-BB-10050
@fixture-OroShoppingListBundle:ProductFixture.yml
Feature: Guest Shopping Lists
  In order to allow unregistered customers to select goods they want to purchase
  As a Sales rep
  I want to enable shopping lists for guest customers

  Scenario: Check Shopping List is not available for a guest on frontend
    And I visit store frontend as guest
    And I should not see "Shopping list"
    And type "SKU003" in "search"
    And I click "Search Button"
    And I should see "Product3"
    Then I should not see "Add to Shopping list"

  Scenario: Check default status of guest shopping list in configurations and enable feature
    Given I login as administrator
    And I go to System/Configuration
    And I click "Commerce" on configuration sidebar
    And I click "Sales" on configuration sidebar
    And I click "Shopping List" on configuration sidebar
    And the "Enable guest shopping list" checkbox should not be checked
   Then uncheck Use Default for "Enable guest shopping list" field
    And I check "Enable guest shopping list"
    And I save setting
    And I should see "Configuration saved" flash message
    And the "Enable guest shopping list" checkbox should be checked

  Scenario: Create Shopping List as unauthorized user from product view page
    And I visit store frontend as guest
    And I should see "Shopping list"
    And type "PSKU1" in "search"
    And I click "Search Button"
    And I should see "Product1"
    And I should see "Add to Shopping list"
    And I click "Product1"
    And I should see "Add to Shopping list"
    And I click "Add to Shopping list"
    And I should see "Product has been added to" flash message
    And I should see "In shopping list"

  Scenario: Check Update Shopping List
    Given I should see "Update Shoppin..."
    And I fill "FrontendLineItemForm" with:
      | Quantity | 10 |
      | Unit | each |
    And I click "Update Shoppin..."
    Then I should see "Record has been succesfully updated" flash message
    And I click "NewCategory"
    Then I should see "In shopping list"

  Scenario: Add more products to shopping list from list page (search)
    And I visit store frontend as guest
    Given I type "CONTROL1" in "search"
    And I click "Search Button"
    And I should see "Control Product"
    When I click "Add to Shopping list"
    Then I should see "Product has been added to" flash message

  Scenario: Check added products available in Guest Shopping List
    When I click "Shopping list"
    And  I should see "Control Product"
    And  I should see "Product1"
    Then I should not see following buttons:
      | Delete        |
      | Create Order  |
      | Request Quote |
