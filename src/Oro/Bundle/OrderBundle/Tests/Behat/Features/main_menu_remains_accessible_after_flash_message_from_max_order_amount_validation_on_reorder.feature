@ticket-BB-25230
@regression
@fixture-OroOrderBundle:MainMenuAfterReorderMaxAmountFixture.yml

Feature: Main menu remains accessible after flash message from max order amount validation on re-order
  In order to use the storefront navigation after a validation message appears
  As a buyer
  I want the main menu to remain accessible when re-order is blocked by maximum order amount setting

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Configure web catalog and set maximum order amount
    Given I proceed as the Admin
    And login as administrator
    And I set "Default Web Catalog" as default web catalog
    And I go to System/Configuration
    And I follow "Commerce/Sales/Checkout" on configuration sidebar
    And uncheck "Use default" for "Maximum Order Amount" field
    And I fill in "Maximum Order Amount USD Config Field" with "1000"
    When I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Re-order is blocked when subtotal exceeds maximum order amount
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I click "Account Dropdown"
    And I click "Order History"
    And I click "Re-Order" on row "TestOrder" in grid "Past Orders Grid"
    Then I should be on Order History page
    And I should see "The order subtotal cannot exceed $1,000.00. Please remove at least $100.00 to proceed." flash message

  Scenario: Main menu is accessible after the validation flash message
    When I open main menu
    Then I should see "Resource Library"
