@regression
@ticket-BB-19649
@fixture-OroInventoryBundle:inventory_levels.yml

Feature: Inventory level export with custom batch size
  To check if the inventory data is exported without duplicates when the batch size limit is exceeded
  As an administrator
  I reduce the batch size limit to 1 and see the exported file has not duplicates

  Scenario: Feature Background
    Given I login as administrator
    And I change the export batch size to 1

  Scenario: Export Detailed inventory levels
    Given I go to Inventory/Manage Inventory
    When click "Export"
    And click on "Detailed inventory levels"
    And click "Export"
    Then I should see "Export started successfully. You will receive email notification upon completion." flash message
    And Email should contains the following "Export performed successfully. 2 inventory levels were exported. Download" text
