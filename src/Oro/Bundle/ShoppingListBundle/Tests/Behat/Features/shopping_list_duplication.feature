@ticket-BB-24899
@regression
@fixture-OroShoppingListBundle:DuplicateShoppingListFixture.yml

Feature: Shopping list duplication
  In order to manager shopping lists on front store
  As a Buyer
  I need to be able to clone shopping list

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator
    And I go to System / Localization / Translations
    And I filter Key as equal to "oro.frontend.shoppinglist.lineitem.unit.label"
    And I edit "oro.frontend.shoppinglist.lineitem.unit.label" Translated Value as "Unit"

  Scenario: Enable required currencies
    Given I go to System/Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    When I fill "Pricing Form" with:
      | Enabled Currencies System | false                     |
      | Enabled Currencies        | [US Dollar ($), Euro (€)] |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Duplicate shopping list
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When Buyer is on "Shopping List A" shopping list
    Then I should see following grid:
      | SKU                                                                     | Qty | Unit  |
      | AA1                                                                     | 10  | items |
      | This item can't be added to checkout because the price is not available |     |       |
      | AA3                                                                     | 20  | items |
      | This item can't be added to checkout because the price is not available |     |       |
    And I click "Shopping List Actions"
    And I click "Duplicate"
    And I click "Yes, duplicate"
    Then I should see "The shopping list has been duplicated" flash message and I close it
    And I should not see "Some products are not available and cannot be added to shopping list" flash message
    And I should see "Shopping List A (copied "
    And I should see following grid:
      | SKU                                                                     | Qty Update All |
      | AA1                                                                     | 10 item        |
      | This item can't be added to checkout because the price is not available |                |
      | AA3                                                                     | 20 item        |
      | This item can't be added to checkout because the price is not available |                |

  Scenario: Duplicate shopping list with restricted items
    When Buyer is on "Shopping List B" shopping list
    Then I should see following grid:
      | SKU                                                                     | Qty | Unit  |
      | AA2                                                                     | 30  | items |
      | This item can't be added to checkout because the price is not available |     |       |
      | AA3                                                                     | 40  | items |
      | This item can't be added to checkout because the price is not available |     |       |

    When I proceed as the Admin
    And I go to Products / Products
    And I click edit AA2 in grid
    And fill "Create Product Form" with:
      | Status | Disable |
    And I save form
    Then I should see "Product has been saved" flash message

    When I proceed as the Buyer
    And I click "Shopping List Actions"
    And I click "Duplicate"
    And I click "Yes, duplicate"
    Then I should see "The shopping list has been duplicated" flash message and I close it
    And I should see "Some products are not available and cannot be added to shopping list" flash message
    And I should see "Shopping List B (copied "
    And I should see following grid:
      | SKU                                                                     | Qty Update All |
      | AA3                                                                     | 40 item        |
      | This item can't be added to checkout because the price is not available |                |
    And I should not see "AA2"

  Scenario: Check duplicate button for shopping list with all restricted items
    When Buyer is on "Shopping List C" shopping list
    And I should see "Some products are not available and have been removed from the shopping list." flash message
    And I click "Close"
    And I click "Shopping List Actions"
    Then I should not see "Duplicate List"

  Scenario: Trying to change currency to EUR and sign out
    Given I click "Account Dropdown"
    And click on "Shopping Lists"
    When I select "€" currency
    Then I should see that "€" currency is active
    When I click "Account Dropdown"
    And click "Sign Out"

  Scenario: Duplicate another owner's shopping list
    Given I proceed as the Buyer
    When I signed in as NancyJSallee@example.org on the store frontend
    And Buyer is on "Shopping List A" shopping list
    Then I should see following grid:
      | SKU                                                                     | Qty | Unit  |
      | AA1                                                                     | 10  | items |
      | This item can't be added to checkout because the price is not available |     |       |
      | AA3                                                                     | 20  | items |
      | This item can't be added to checkout because the price is not available |     |       |

    And click "Shopping List Actions"
    And click "Duplicate"
    And click "Yes, duplicate"
    Then I should see "The shopping list has been duplicated" flash message and I close it
    And should see "Shopping List A (copied "
    And should see following grid:
      | SKU                                                                     | Qty Update All |
      | AA1                                                                     | 10 item        |
      | This item can't be added to checkout because the price is not available |                |
      | AA3                                                                     | 20 item        |
      | This item can't be added to checkout because the price is not available |                |
