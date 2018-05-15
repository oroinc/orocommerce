@fixture-OroProductBundle:mass_delete_action_in_products_grid.yml
@regression
Feature: Mass delete action in Products Grid
  Check that mass delete action works correctly

  Scenario: Delete few manually selected records
    Given login as administrator
    And go to Products/ Products
    And I keep in mind number of records in list
    When I check first 2 records in grid
    And I click "Delete" link from mass action dropdown
    And confirm deletion
    Then I should see "2 entities have been deleted successfully" flash message
    And the number of records decreased by 2
