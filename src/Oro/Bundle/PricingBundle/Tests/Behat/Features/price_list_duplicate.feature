@regression
@ticket-BB-18672
@fixture-OroPricingBundle:PriceListWithRules.yml

Feature: Price list duplicate
  In order to use effectively work with price lists
  As an Administrator
  I want to have ability to duplicate existing price lists

  Scenario: Feature Background
    Given I login as administrator
    And I go to Sales/ Price Lists
    And click View Price List with Rule in grid

  Scenario: Duplicate Price List
    When I click "Duplicate Price List"
    Then I should not see "This price list is currently being recalculated. You may make further edits, but please allow for additional time to see the updated prices on the store frontend."
    And I should see "Copy of Price List with Rule"
    And number of records in "Price list Product prices Grid" should be 0

  Scenario: Activate Price List
    When I click "Enable"
    Then I should see "Price List was enabled successfully" flash message

  Scenario: Check activated Price List prices
    When I go to Sales/ Price Lists
    And click View Copy of Price List with Rule in grid
    Then number of records in "Price list Product prices Grid" should be 1
