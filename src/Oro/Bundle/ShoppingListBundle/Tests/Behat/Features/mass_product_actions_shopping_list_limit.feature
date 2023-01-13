@fixture-OroShoppingListBundle:ProductsMassAction.yml
Feature: Mass Product Actions shopping list limit

  In order to allow creation of only allowed amount of shopping lists
  As an Administrator
  I want to have ability to set limit of shopping lists that affects mass actions work

  Scenario: Create different window session
    Given sessions active:
      | Admin  |first_session |
      | User   |second_session|

  Scenario: Setting shopping list limit in management console
    Given I proceed as the Admin
    And login as administrator
    And I go to System/ Configuration
    And follow "Commerce/Sales/Shopping List" on configuration sidebar
    And I fill form with:
      | Use default         | false |
      | Shopping List Limit | 1     |
    And save form
    Then I should see "Configuration saved" flash message

  Scenario: Shopping List can be added if Shopping List limit is not reached
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    When I type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "PSKU1"
    And I check PSKU1 record in "Product Frontend Grid" grid
    And I click "Create New Shopping List" in "ProductFrontendMassPanelInBottomSticky" element
    Then should see an "Create New Shopping List popup" element
    When I type "First Shopping List" in "Shopping List Name"
    And click "Create and Add"
    Then I should see '1 product was added (view shopping list)' flash message
    When I hover on "Shopping Cart"
    Then I should see "View List"

  Scenario: "Create New Shopping List" action is not available when Shopping List limit is less or equals the number of Shopping Lists
    When I check PSKU1 record in "Product Frontend Grid" grid
    And I should see "ProductFrontendMassPanelInBottomSticky" element inside "Bottom Active Sticky Panel" element
    And I should not see "ProductFrontendMassOpenInDropdown"
    Then I should not see "Create New Shopping List" in the "ProductFrontendMassPanelInBottomSticky" element
    And I uncheck PSKU1 record in "Product Frontend Grid" grid

  Scenario: Shopping List can not be added when Shopping List limit is less or equals the number of Shopping Lists
    # Increase limit to allow to chose create shopping list mass action
    Given I proceed as the Admin
    And I fill form with:
      | Shopping List Limit | 2 |
    And save form
    Then I should see "Configuration saved" flash message

    When I proceed as the User
    And I type "PSKU2" in "search"
    And I click "Search Button"
    Then I should see "PSKU2"
    When I check PSKU2 record in "Product Frontend Grid" grid
    And I click "ProductFrontendMassOpenInDropdown"
    And I click "Create New Shopping List" in "ProductFrontendMassMenuInBottomSticky" element
    Then should see an "Create New Shopping List popup" element
    And I type "Second Shopping List" in "Shopping List Name"

    # Decrease limit to check that shopping list is not added via dialog
    When I proceed as the Admin
    And I fill form with:
      | Shopping List Limit | 1 |
    And save form
    Then I should see "Configuration saved" flash message

    When I proceed as the User
    And click "Create and Add"
    When I hover on "Shopping Cart"
    Then I should not see "Second Shopping List"
