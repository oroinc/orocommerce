@regression
@feature-BB-24183
@fixture-OroProductBundle:inventory_filter.yml
Feature: Inventory Filter configuration for Customer Users

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Update product inventory status
    Given I proceed as the Admin
    When I login as administrator
    And I go to Products/Products
    And I edit "SKU1" Inventory status as "Out of Stock" by double click
    And I click "Save changes"
    Then I should see "Record has been successfully updated" flash message

  Scenario: Choose In Stock Statuses For Simple Filter
    When go to System / Configuration
    And I follow "Commerce/Inventory/Allowed Statuses" on configuration sidebar
    And uncheck "Use default" for "In Stock Statuses For Simple Filter" field
    And fill form with:
      | In Stock Statuses For Simple Filter | Out of Stock |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Show Inventory filter
    Given I proceed as the Buyer
    When I signed in as AmandaRCole@example.org on the store frontend
    And type "SKU" in "search"
    And I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 3
    And I should see "SKU1" product
    And I should see "SKU2" product
    And I should see "SKU3" product
    And I should see available "In Stock Only" filter in frontend grid

  Scenario: Check Simple Inventory filter
    When I click "Inventory Status Switcher"
    Then number of records in "Product Frontend Grid" should be 1
    And I should see "SKU1" product
    And I should not see "SKU2" product
    And I should not see "SKU3" product
    And I should see filter hints in frontend grid:
      | In Stock Only |

    When I click "Inventory Status Switcher"
    Then number of records in "Product Frontend Grid" should be 3
    And I should see "SKU1" product
    And I should see "SKU2" product
    And I should see "SKU3" product

  Scenario: Set Inventory Filter Type as Multi-Select
    Given I proceed as the Admin
    When uncheck "Use default" for "Inventory Filter Type" field
    And fill form with:
      | Inventory Filter Type | Multi-Select |
    Then I should not see an "In Stock Statuses For Simple Filter Select" element
    When I save form
    Then I should see "Configuration saved" flash message

  Scenario: Check Multi-Select Inventory filter
    Given I proceed as the Buyer
    When I reload the page
    Then I should see "Inventory Status" filter in frontend grid

    When I check "In Stock" in Inventory Status filter in frontend product grid
    Then number of records in "Product Frontend Grid" should be 2
    And I should see "SKU2" product
    And I should see "SKU3" product
    And I should not see "SKU1" product
    And I should see filter hints in frontend grid:
      | In Stock |

    When I click "Clear All Filters"
    Then number of records in "Product Frontend Grid" should be 3
    And I should see "SKU1" product
    And I should see "SKU2" product
    And I should see "SKU3" product

  Scenario: Hide Inventory Filter to guest visitors
    Given I proceed as the Admin
    When uncheck "Use default" for "Enable for Guests" field
    And I uncheck "Enable for Guests"
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Show Inventory filter for Customer User
    Given I proceed as the Buyer
    When I reload the page
    Then I should see "Inventory Status" filter in frontend grid
