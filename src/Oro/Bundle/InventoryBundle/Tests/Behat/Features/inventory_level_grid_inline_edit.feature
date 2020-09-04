@regression
@ticket-BB-19607
@fixture-OroUserBundle:UserLocalizations.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroWarehouseBundle:DecrementQuantity.yml
@fixture-OroWarehouseBundle:TestWarehouse.yml
@fixture-OroWarehouseBundle:InventoryLevel.yml

Feature: Inventory Level Grid inline edit

  Scenario: Create string field in Inventory Level entity
    Given login as administrator
    And I go to System/Entities/Entity Management
    And I filter Name as contains "Inventory"
    And I click View Inventory Level in grid
    And I click "Create field"
    And I fill form with:
      | Field Name   | test         |
      | Storage Type | Table column |
      | Type         | String       |
    And I click "Continue"
    And I save and close form
    Then I should see "Field saved" flash message
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Enable French localization
    When I go to System / Configuration
    And I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And I fill form with:
      | Enabled Localizations | [English (United States), French Localization] |
      | Default Localization  | French Localization                            |
    And I submit form
    Then I should see "Configuration saved" flash message

  Scenario: Check inventory level inline edit
    Given I go to Inventory/ Manage Inventory
    When I edit "Lenovo_Vibe1_sku" test as "Test value" by double click
    And I click "Save changes"
    Then I should see "Record has been successfully updated" flash message
