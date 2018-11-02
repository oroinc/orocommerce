@ticket-BB-15375
@fixture-OroShoppingListBundle:ShoppingListForValidationMessages.yml
Feature: Shopping list inventory validation messages
  In order to be informed about inventory restrictions at shopping list
  As Frontend User
  I need to see validation messages in proper places

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Validation messages in proper places
    Given I proceed as the Admin
    And I login as administrator
    When I go to Products/Products
    And click edit SKU123 in grid
    And I click "Inventory" in scrollspy
    And I fill "Create Product Form" with:
      | Minimum Quantity To Order Use | false |
      | Minimum Quantity To Order     | 5     |
      | Maximum Quantity To Order Use | false |
      | Maximum Quantity To Order     | 10    |
    And I save and close form
    Then I should see "Product has been saved" flash message
    When I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I hover on "Shopping List Widget"
    And I click "View Shopping List Details"
    And I should not see an "Shopping List Line Item Error" element
    When I fill "Shopping List Line Item 2 Form" with:
      | Quantity | 4 |
    Then I should not see "Shopping List Line Item Error" element with text "You cannot order less 5 units of SKU123: testname" inside "Shopping List Line Item 1" element
    And I should see "Shopping List Line Item Error" element with text "You cannot order less 5 units of SKU123: testname" inside "Shopping List Line Item 2" element
    When I fill "Shopping List Line Item 1 Form" with:
      | Quantity | 4 |
    Then I should see "Shopping List Line Item Error" element with text "You cannot order less 5 units of SKU123: testname" inside "Shopping List Line Item 1" element
    And I should see "Shopping List Line Item Error" element with text "You cannot order less 5 units of SKU123: testname" inside "Shopping List Line Item 2" element
    When I fill "Shopping List Line Item 1 Form" with:
      | Quantity | 11 |
    Then I should see "Shopping List Line Item Error" element with text "You cannot order more than 10 units of SKU123: testname" inside "Shopping List Line Item 1" element
    And I should not see "Shopping List Line Item Error" element with text "You cannot order less 5 units of SKU123: testname" inside "Shopping List Line Item 1" element
    And I should see "Shopping List Line Item Error" element with text "You cannot order less 5 units of SKU123: testname" inside "Shopping List Line Item 2" element
    And I should not see "Shopping List Line Item Error" element with text "You cannot order more than 10 units of SKU123: testname" inside "Shopping List Line Item 2" element
