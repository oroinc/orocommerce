@regression
@ticket-BB-9199
@fixture-OroProductBundle:product_check_category.yml
Feature: Product admin view page category displaying

  Scenario: Displaying only one category
    Given I login as administrator
    And I go to Products/ Products
    And I click edit "PSKU3" in grid
    And I click "All Products"
    When I save and close form
    Then I should see "Product has been saved" flash message
    And I should see "All Products"

  Scenario: Displaying tree with two categories
    Given I go to Products/ Products
    When I click View "PSKU1" in grid
    Then I should see "All Products / NewCategory"

  Scenario: Displaying tree with three categories
    Given I go to Products/ Products
    When I click View "PSKU2" in grid
    Then I should see "ALL Products /.../ NewCategory2"
