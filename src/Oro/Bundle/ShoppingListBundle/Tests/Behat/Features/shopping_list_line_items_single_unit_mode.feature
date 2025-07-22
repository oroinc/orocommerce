@regression
@ticket-BB-18293
@fixture-OroShoppingListBundle:ShoppingListFixture.yml
Feature: Shopping List Line Items Single Unit Mode
  In order to manager shopping lists on front store
  As a Buyer
  I need to be able to update shopping list with single unit mode

  Scenario: Create different window sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Enable Single Unit mode
    Given I proceed as the Admin
    And I login as administrator
    When I go to System/ Configuration
    And I follow "Commerce/Product/Product Unit" on configuration sidebar
    And uncheck "Use default" for "Single Unit" field
    And I check "Single Unit"
    And uncheck "Use default" for "Default Primary Unit" field
    And I select "item" from "Default Primary Unit"
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario: Update line items without units
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And Buyer is on "Shopping List 5" shopping list
    When I click "Shopping List Actions"
    And I click "Edit"
    Then I should see following grid:
      | SKU | Qty Update All |
      | AA1 | 1              |
      | AA1 | 2              |
    When I click on "Shopping List Line Item 2 Quantity"
    And I fill "Shopping List Line Item Form" with:
      | Quantity | 1 |
    And I click on "Shopping List Line Item 1 Quantity"
    And I fill "Shopping List Line Item Form" with:
      | Quantity | 3 |
    And I click "Update All"
    Then I should see following grid:
      | SKU | Qty Update All |
      | AA1 | 3              |
      | AA1 | 1              |

  Scenario: Check Units In The Shopping List Widget With Disabled Units
    Given I open shopping list widget
    Then I should see "Qty: 2" in the "Shopping List Widget" element

  Scenario: Enable Show Single Unit
    Given I proceed as the Admin
    And uncheck "Use default" for "Show Unit Code" field
    And I check "Show Unit Code"
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario: Update line items with single unit
    Given I proceed as the Buyer
    And I reload the page
    Then I should see following grid:
      | SKU | Qty Update All |
      | AA1 | 3 set          |
      | AA1 | 1 item         |
    When I click on "Shopping List Line Item 2 Quantity"
    And I fill "Shopping List Line Item Form" with:
      | Quantity | 4 |
    And I click on "Shopping List Line Item 1 Quantity"
    And I fill "Shopping List Line Item Form" with:
      | Quantity | 5 |
    And I click "Update All"
    Then I should see following grid:
      | SKU | Qty Update All |
      | AA1 | 5 set          |
      | AA1 | 4 item         |

  Scenario: Check Units In The Shopping List Widget With Enabled Units
    Given I open shopping list widget
    Then I should see "2 Items" in the "Shopping List Widget" element

  Scenario: Check shopping list view with visible single item unit
    Given I click "Account Dropdown"
    And I click on "Shopping Lists"
    And I click view "Shopping List 5" in grid
    And I should see following grid:
      | Sku | Product  | Availability | Qty |       | Price | Subtotal |
      | AA1 | Product1 | In Stock     | 5   | sets  |       |          |
      | AA1 | Product1 | In Stock     | 4   | items |       |          |

  Scenario: Disable Show Single Unit
    Given I proceed as the Admin
    And check "Use default" for "Show Unit Code" field
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario: Check shopping list view without visible single item unit
    Given I proceed as the Buyer
    And I reload the page
    And I should see following grid:
      | Sku | Product  | Availability | Qty | Price | Subtotal |
      | AA1 | Product1 | In Stock     | 5   |       |          |
      | AA1 | Product1 | In Stock     | 4   |       |          |
