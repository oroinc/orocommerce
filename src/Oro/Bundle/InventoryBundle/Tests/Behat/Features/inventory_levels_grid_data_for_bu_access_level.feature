@regression
@ticket-BB-20396
@fixture-OroInventoryBundle:product_inventory_levels_bu_access_level.yml

Feature: Inventory Levels Grid data for BU access level
    In order to be sure user can see inventory levels for products that belong to his BU
    As a backoffice user
    I want to be able to see correct data in Inventory Levels grid

    Scenario: Check initial Inventory Levels grid
        Given I login as administrator
        When I go to Inventory/Manage Inventory
        Then there are 2 records in grid

    Scenario: Setup Sales Manager Role
        When I go to System/ User Management/ Roles
        And I click view "Sales Manager" in grid
        When I click "Edit"
        And select following permissions:
            | Product         | View:Business Unit |
            | Inventory Level | View:Organization  |
        And save and close form
        Then I should see "Role saved" flash message

    Scenario: Check Inventory Levels grid for user with limited access
        Given I login as "charlie" user
        When I go to Inventory/Manage Inventory
        Then there are 1 records in grid
