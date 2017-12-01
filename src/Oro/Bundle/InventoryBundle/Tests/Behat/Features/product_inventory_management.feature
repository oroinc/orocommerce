@fixture-OroProductBundle:Products_quick_order_form.yml
Feature: Product inventory management
  In order to simplify the way of displaying large values of quantity fields while viewing them in the grid
  As an Administrator
  I want to keep formatting values in the grid while viewing them, and I want to see the values without formatting while editing them through the system

  Scenario: Check inventory popup in product view
    Given I login as administrator
    And I go to Products/ Products
    And I click View "PSKU1" in grid
    And I click "More actions"
    And I click on "Manage Inventory"
    And I fill "Manage Inventory Form" with:
      | Quantity1 | 10000000 |
    # Check there was no real time input formatting
    Then "Manage Inventory Form" must contains values:
      | Quantity1 | 10000000 |
    And I click "Save"
    And I click "More actions"
    And I click on "Manage Inventory"
    # Check there was no formatting after saving
    Then "Manage Inventory Form" must contains values:
      | Quantity1 | 10000000 |
    And I click "Cancel"

  Scenario: Check Inventory grid edit/view mode Quantity field
    Given I go to Inventory/ Manage Inventory
    Then I should see following records in grid:
      | 10,000,000 |
    # Check there's no formatting in the inline editor field
    When I start inline editing on "Test Warehouse" "Quantity" field I should see "10000000" value
    And I click on empty space
    Then I edit "Test Warehouse" Quantity as "1000000000" with click on empty space
    When I start inline editing on "Test Warehouse" "Quantity" field I should see "1000000000" value
    And I click on empty space
    Then I should see following records in grid:
      | 1,000,000,000 |
