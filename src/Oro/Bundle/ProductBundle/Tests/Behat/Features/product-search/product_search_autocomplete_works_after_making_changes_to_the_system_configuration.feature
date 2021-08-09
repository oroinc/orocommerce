@regression
@ticket-BB-20741
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
Feature: Product search autocomplete works after making changes to the system configuration
  Changing system parameters should not affect the search autocomplete, so change the parameters
  and check that the search autocomplete works correctly.

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  # Be sure to change the number of search results to update the cached data.
  Scenario: Change "Number Of Products In Search Autocomplete" configuration
    Given I proceed as the Admin
    And login as administrator
    And go to System/Configuration
    And follow "Commerce/Product/Product Search" on configuration sidebar
    And uncheck "Use default" for "Number Of Products In Search Autocomplete" field
    And fill form with:
      | Number Of Products In Search Autocomplete | 4 |
    When I click "Save settings"
    Then I should see "Configuration saved" flash message

  # It is enough to change any system configuration parameter to detect a bug.
  Scenario: Enable Guest Checkout
    Given I go to System/Configuration
    And go to System/Configuration
    And follow "Commerce/Sales/Checkout" on configuration sidebar
    And uncheck "Use default" for "Guest Checkout" field
    And check "Guest Checkout"
    When I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check the search autocomplete after system setting has been changed
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I type "Search string" in "search"
    And should see an "Search Autocomplete" element
    Then I should see "No products were found to match your search" in the "Search Autocomplete No Found" element
    And should not see "There was an error performing the requested operation. Please try again or contact us for assistance." flash message
