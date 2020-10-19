@fixture-OroShoppingListBundle:ShoppingListsWidgetFixture.yml
Feature: Shopping Lists Widget
  In order to allow customers to see products they want to purchase
  As a Buyer
  I need to be able to view a shopping list widget

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator

  Scenario: Check Show All in Shopping Lists Widget is enabled
    Given I go to System/ Configuration
    When I follow "Commerce/Sales/Shopping List" on configuration sidebar
    Then the "Show All Lists in Shopping List Widget" checkbox should be checked

  Scenario: Check widget with enabled Show All in Shopping Lists Widget
    Given I operate as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I open shopping list widget
    Then I should see "Shopping List 1" in the "Shopping List Widget" element
    And I should see "Shopping List 2" in the "Shopping List Widget" element

  Scenario: Disable Show All in Shopping Lists Widget
    Given I proceed as the Admin
    When I fill "Shopping List Configuration Form" with:
      | Show All Lists in Shopping List Widget Use default | false |
      | Show All Lists in Shopping List Widget             | false |
    And click "Save settings"
    Then I should see "Configuration saved" flash message
    And the "Show All Lists in Shopping List Widget" checkbox should be unchecked

  Scenario: Check widget with disabled Show All in Shopping Lists Widget
    Given I operate as the Buyer
    And I reload the page
    When I open shopping list widget
    Then I should see "Shopping List 1" in the "Shopping List Widget" element
    And I should not see "Shopping List 2" in the "Shopping List Widget" element
