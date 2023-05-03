@regression
@ticket-BB-21413
@fixture-OroProductBundle:single_product.yml
Feature: Allowed inventory statuses should be applied when set only at website level

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Use default inventory statuses on system level
    Given I proceed as the Admin
    And I login as administrator
    And I go to System/Configuration
    When I follow "Commerce/Inventory/Allowed Statuses" on configuration sidebar
    And check "Use default" for "Visible Inventory Statuses" field
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Enable all available inventory statuses on website level
    Given I proceed as the Admin
    And I go to System/Websites
    And I click Configuration Default in grid
    When I follow "Commerce/Inventory/Allowed Statuses" on configuration sidebar
    And uncheck "Use Organization" for "Visible Inventory Statuses" field
    And I fill form with:
      | Visible Inventory Statuses | [In Stock, Out of Stock, Discontinued] |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Prepare discontinued product
    Given I proceed as the Admin
    And I go to Products/ Products
    And edit "PSKU1" Inventory status as "Discontinued" by double click
    And I click "Save changes"
    Then I should see "Record has been successfully updated" flash message

  Scenario: Check the search, discontinued product should appear
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I type "psku1" in "search"
    And click "Search Button"
    Then number of records in "Product Frontend Grid" should be 1
