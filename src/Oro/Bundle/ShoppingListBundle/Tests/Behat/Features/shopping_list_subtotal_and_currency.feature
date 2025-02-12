@regression
@ticket-BB-14689
@fixture-OroShoppingListBundle:MyShoppingListsFixture.yml
Feature: Shopping list subtotal and currency
  In order to manage shopping lists on back office
  As an Admin
  I need to be able to see right subtotal and currency

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Configure pricing
    Given I proceed as the Admin
    And I login as administrator
    When I go to System / Configuration
    And I follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "Pricing Form" with:
      | Enabled Currencies System | false                     |
      | Enabled Currencies        | [US Dollar ($), Euro (€)] |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Buyer creates a shopping list on the storefront
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I select "€" currency
    And I am on the homepage
    And I open shopping list widget
    And I click "Create New List"
    When I click "Create"
    Then I should see "3" in the "Shopping List Widget" element
    And I type "BB04" in "search"
    And I click "Search Button"
    Then I should see "Add to Shopping List"
    When I click "Add to Shopping List"
    And I should see 'Product has been added to "Shopping List"' flash message

  # These scenarios ensure shopping_list_total is updated with isValid = true
  Scenario: Buyer (Amanda) updates Shopping List currency to USD
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    And I click on "Shopping Lists"
    And I select "$" currency
    And I click "Account Dropdown"
    And I click on "Shopping Lists"

  Scenario: Buyer (Nancy) updates Shopping List currency to EUR
    Given I proceed as the Buyer
    And I signed in as NancyJSallee@example.org on the store frontend
    And I click "Account Dropdown"
    And I click on "Shopping Lists"
    And I select "€" currency
    And I click "Account Dropdown"
    And I click on "Shopping Lists"

  Scenario: Admin verifies shopping list subtotals and currencies
    Given I proceed as the Admin
    And I go to Sales/Shopping Lists
    And records in grid should be 4
    And I should see following grid:
      | Label           | Subtotal  |
      | Shopping List   | €11.00    |
      | Shopping List 3 | $8,818.00 |
      | Shopping List 2 | $1,178.00 |
      | Shopping List 1 | $1,581.00 |
    And click view "$8,818.00" in grid
    And I scroll to text "Totals"
    And I should see "$8,818.00"
    And I should see "-$647.50"
    And I should see "$8,170.50"
    And I go to Sales/Shopping Lists
    And click view Shopping List in grid
    And I scroll to text "Totals"
    And I should see "€11.00"
    And I should see "-€5.50"
    And I should see "€5.50"
