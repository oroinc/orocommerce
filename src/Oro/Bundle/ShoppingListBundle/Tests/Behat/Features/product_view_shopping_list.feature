@ticket-BB-13978
@fixture-OroShoppingListBundle:product_shopping_list.yml

Feature: Product view shopping list
  In order to edit content node
  As an Buyer
  I want to have ability to create quote on product page.
  I need to be able to see localized product name in Shopping Lists widget.

  Scenario: Create different window session
    Given sessions active:
      | User  |first_session |
      | Admin |second_session|
    And I enable the existing localizations

  Scenario: Requests a quote button exists in shopping list dropdown after changing units
    Given I proceed as the User
    Given I signed in as AmandaRCole@example.org on the store frontend
    And type "PSKU1" in "search"
    And click "Search Button"
    And click "View Details" for "PSKU1" product
    And I click on "Shopping List Dropdown"
    And I should see "Request A Quote Button" element inside "Product Shopping List Dropdown" element
    When I fill "Product Shopping List Form" with:
      | Unit | set |
    And I click on "Shopping List Dropdown"
    And I should see "Request A Quote Button" element inside "Product Shopping List Dropdown" element
    And I click "Request A Quote Button"
    And I should see "REQUEST A QUOTE" in the "RequestForQuoteTitle" element

  Scenario: Adding to shopping list product with single, default unit in globally set single unit mode
    Given I proceed as the Admin
    And login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Product/Product Unit" on configuration sidebar
    And uncheck "Use default" for "Single Unit" field
    And I check "Single Unit"
    And uncheck "Use default" for "Default Primary Unit" field
    And select "item" from "Default Primary Unit"
    And I save setting
    And I proceed as the User
    And type "PSKU_ITEM" in "search"
    And click "Search Button"
    And click "View Details" for "PSKU_ITEM" product
    When I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message and I close it

  Scenario: Check that product name is displayed properly in shopping cart dropdown
    And type "PSKU1" in "search"
    And click "Search Button"
    And click "View Details" for "PSKU1" product
    And I click "Add to Shopping List"
    And I am on the homepage
    When I hover on "Shopping Cart"
    Then I should see "Product1`\"'&йёщ®&reg;>"
    And I should not see "Product1`\"'&йёщ®®>"

  Scenario: Check that product name is displayed properly on shopping list view page
    When I open page with shopping list Shopping List
    Then I should see "Product1`\"'&йёщ®&reg;>"
    And I should not see "Product1`\"'&йёщ®®>"

  Scenario: Check that product name is localized in shopping lists widget
    Given I click "Localization Switcher"
    And I select "Localization 1" localization
    And type "PSKU_LOCALIZED" in "search"
    And click "Search Button"
    And click "View Details" for "PSKU_LOCALIZED" product
    And I click "Add to Shopping List"
    When click "In Shopping List"
    Then I should see "UiDialog" with elements:
      | Title | Product 3 (Localization1)|
