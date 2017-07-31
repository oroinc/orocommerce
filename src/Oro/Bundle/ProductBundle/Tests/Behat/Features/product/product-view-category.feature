@ticket-BB-9199
@fixture-OroProductBundle:product_check_category.yml
Feature: Product admin view page category displaying

  Scenario: Displaying only one category
    Given I login as administrator
    And I go to Products/ Products
    And I click View "PSKU1" in grid
    Then I should see "NewCategory"

  Scenario: Displaying tree with two categories
    Given I login as administrator
    And I go to Products/ Products
    And I click View "PSKU2" in grid
    Then I should see "NewCategory / NewCategory2"

  Scenario: Displaying tree with three categories
    Given I login as administrator
    And I go to Products/ Products
    And I click View "PSKU3" in grid
    Then I should see "NewCategory /.../ NewCategory3"