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

  Scenario: Check Show All in Shopping Lists Widget is disabled
    Given I go to System/ Configuration
    When I follow "Commerce/Sales/Shopping List" on configuration sidebar
    Then the "Show All Lists in Shopping List Widgets" checkbox should not be checked

  Scenario: Check widget with disabled Show All in Shopping Lists Widget
    Given I operate as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I open shopping list widget
    Then I should see "Shopping List 1" in the "Shopping List Widget" element
    And I should not see "Shopping List 2" in the "Shopping List Widget" element

  Scenario: Check buttons with disabled Show All in Shopping Lists Widget
    Given I click "New Category"
    And I should not see "Remove from Shopping List 1"
    And I should not see "Remove from Shopping List 2"
    When I click on "Shopping List Dropdown"
    And I should see "Remove from Shopping List 1"
    And I should not see "Remove from Shopping List 2"

  Scenario: Check mass action with disabled Show All in Shopping Lists Widget
    When I check AA1 record in "Product Frontend Grid" grid
    And I should see "ProductFrontendMassPanelInBottomSticky" element inside "Bottom Active Sticky Panel" element
    And I click "ProductFrontendMassOpenInDropdown"
    Then I should see "Add to Shopping List 1" in the "ProductFrontendMassMenuInBottomSticky" element
    And I should not see "Add to Shopping List 2" in the "ProductFrontendMassMenuInBottomSticky" element
    And I uncheck AA1 record in "Product Frontend Grid" grid

  Scenario: Check buttons widget with disabled Show All in Shopping Lists Widget
    When click "In Shopping List" for "AA1" product
    Then I should see next rows in "In Shopping Lists" table in the exact order
      | Shopping List   | QTY #  |
      | Shopping List 1 | 5items |
    And I click "Choose list"
    And I should see the following options for "List" select in form "ShoppingListForm":
      | Shopping List 1 |
    And I should not see the following options for "List" select in form "ShoppingListForm":
      | Shopping List 2 |

  Scenario: Enable Show All in Shopping Lists Widget
    Given I proceed as the Admin
    When I fill "Shopping List Configuration Form" with:
      | Show All Lists in Shopping List Widgets Use default | false |
      | Show All Lists in Shopping List Widgets             | true  |
    And click "Save settings"
    Then I should see "Configuration saved" flash message
    And the "Show All Lists in Shopping List Widgets" checkbox should be checked

  Scenario: Check widget with enabled Show All in Shopping Lists Widget
    Given I operate as the Buyer
    And I reload the page
    When I open shopping list widget
    Then I should see "Shopping List 1" in the "Shopping List Widget" element
    And I should see "Shopping List 2" in the "Shopping List Widget" element

  Scenario: Check buttons with enabled Show All in Shopping Lists Widget
    Given I click "New Category"
    And I should not see "Remove from Shopping List 1"
    And I should not see "Remove from Shopping List 2"
    When I click on "Shopping List Dropdown"
    And I should see "Remove from Shopping List 1"
    And I should see "Remove from Shopping List 2"

  Scenario: Check mass action with enabled Show All in Shopping Lists Widget
    When I check AA1 record in "Product Frontend Grid" grid
    And I should see "ProductFrontendMassPanelInBottomSticky" element inside "Bottom Active Sticky Panel" element
    And I click "ProductFrontendMassOpenInDropdown"
    Then I should see "Add to Shopping List 1" in the "ProductFrontendMassMenuInBottomSticky" element
    And I should see "Add to Shopping List 2" in the "ProductFrontendMassMenuInBottomSticky" element
    And I uncheck AA1 record in "Product Frontend Grid" grid

  Scenario: Check buttons widget with enabled Show All in Shopping Lists Widget
    When click "In Shopping List" for "AA1" product
    Then I should see next rows in "In Shopping Lists" table in the exact order
      | Shopping List   | QTY #  |
      | Shopping List 1 | 5items |
      | Shopping List 2 | 2items |
    And I click "Choose list"
    And I should see the following options for "List" select in form "ShoppingListForm":
      | Shopping List 2 |
      | Shopping List 1 |
