@regression
@ticket-BB-9198
@fixture-OroProductBundle:product_check_category.yml
Feature: Product admin view page inventory status color

  Scenario: Checking "in stock" status color
    Given I login as administrator
    And I go to Products/ Products
    And I click View "PSKU1" in grid
    Then I should see that "InStockInventoryStatus" contains "In Stock"

  Scenario: Checking "out of stock" status color
    Given I go to Products/ Products
    And I click View "PSKU2" in grid
    Then I should see that "OutOfStockInventoryStatus" contains "Out of Stock"
