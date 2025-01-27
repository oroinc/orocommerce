@regression
@postgresql
@ticket-BB-25067

Feature: Price list enum rules validation
  In order to use effectively work with price lists
  As an Administrator
  I want to have ability to get information about invalid rules with enumerable fields before price list save

  Scenario: Check price list product assignment rule with enumerable field validation
    Given I login as administrator
    And I go to Sales/ Price Lists
    When I click "Create Price List"
    And I fill form with:
      | Name       | Base Price List                                           |
      | Currencies | US Dollar ($)                                             |
      | Active     | true                                                      |
      | Rule       | product.inventory_status.undefinedEnumField == 'in_stock' |
    When I save and close form
    Then I should see validation errors:
      | Rule | Invalid expression; Field "undefinedEnumField" is not allowed to be used |
    And I fill form with:
      | Rule | product.inventory_status.internalId == 'in_stock' |
    And I save and close form
    Then I should see "Price List has been saved" flash message
