@ticket-BB-12088
@fixture-OroProductBundle:product_check_category.yml
Feature: Product category grid actions
  In order to check actions in Product Grid
  As an Administrator
  I want to check that set of actions is not changed after filtering by Category

  Scenario: Check actions in grid in category
    Given I login as administrator
    And I go to Products/ Products
    And I should see following actions for PSKU2 in grid:
      | Duplicate |
      | View      |
      | Edit      |
      | Delete    |
    #add filter to check that operations still available
    And expand "NewCategory" in tree
    And I click "NewCategory2"
    And I filter SKU as is equal to "PSKU2"
    Then I should see following actions for PSKU2 in grid:
      | Duplicate |
      | View      |
      | Edit      |
      | Delete    |
