@regression
@ticket-BB-16745
@fixture-OroInventoryBundle:product_inventory_levels.yml
@community-edition-only
Feature: Inventory Levels Main Grid

    Scenario: Inventory -> Inventory Levels page opens
        Given I login as administrator
        When I go to Inventory/Manage Inventory
        Then there are 2 records in grid
        When I check "Piece" in Unit filter
        Then there are zero records in grid
        When I check "Item" in Unit filter
        Then there is 1 records in grid
        When I check "Set" in Unit filter
        Then there is 2 records in grid
