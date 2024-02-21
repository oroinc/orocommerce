@fixture-OroSaleBundle:QuoteViewFrontend.yml
@ticket-BB-22495
@regression

Feature: Quote View Frontend with shopping list limit equals one

  In order to ensure frontend quote view page works correctly with shopping list limit equals one and created
    shopping list
  As a buyer
  I check product names are displayed properly on quote view page

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Set shopping list limit to one
    Given I proceed as the Admin
    And I login as administrator
    And go to System/Configuration
    And follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Shopping List Limit" field
    And I fill in "Shopping List Limit" with "1"
    And I save setting
    And I should see "Configuration saved" flash message

  Scenario: Create shopping list
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on homepage
    And I type "psku1" in "search"
    And I click "psku1"
    When I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message and I close it

  Scenario: Check quote view page
    When I click "Account Dropdown"
    And I click "Quotes"
    And click view "Q123" in grid
    Then I should see "Product1`\"'&йёщ®&reg;>"
    And I should not see "Product1`\"'&йёщ®®>"
