@regression
@fixture-OroShoppingListBundle:MyShoppingListsFixture.yml

Feature: Shopping list Save For Later feature disabled
  Scenario: Disable Save For Later Feature
    Given I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And I fill "Shopping List Configuration Form" with:
      | Enable Save For Later Use default | false |
      | Enable Save For Later             | false |
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario: Verify Saved For Later grid is hidden in admin panel when feature disabled
    Given I login as administrator
    When I go to Sales/Shopping Lists
    And I click View Shopping List in grid
    Then I should not see "Shopping List Saved For Later Line Items Grid" grid

  Scenario: Verify Saved For Later functionality is hidden in storefront when feature disabled
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list Shopping List 1
    Then I should not see "Frontend Shopping List Saved For Later Line Items Grid" grid
    And I shouldn't see Save For Later action in "Frontend Shopping List Edit Grid"
    When I check first 1 records in "Frontend Shopping List Edit Grid"
    Then I should not see following actions for CC37 in "Frontend Shopping List Edit Grid":
      | Save For Later |


