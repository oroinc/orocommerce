@fixture-ShoppingListRule.yml
Feature: Shopping Lists
  Scenario: "Shopping List > Request A Quote" #1
    Given I login as AmandaRCole@example.org buyer
    When Buyer is on Shopping List 1
    And There it Requested a quote
    Then it on page Request For Quote and see message Request has been saved
