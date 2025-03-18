@feature-BB-24920
@regression
@fixture-OroCommerceBundle:CustomerUserFixture.yml
@fixture-OroCommerceBundle:ProductFixture.yml
@fixture-OroCommerceBundle:ShoppingListFixture.yml

Feature: Verify Shopping List Management on Customer Dashboard
  In order to ensure correct functionality of the shopping list widget and its actions
  As an administrator
  I should be able to verify its display, item count, navigation, and renaming actions

  Scenario: Validate Shopping List Widget and Rename a List to check order of elements in the grid
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    When I click "Dashboard"
    Then I should see that "My Shopping Lists Widget" contains "My Shopping Lists"
    And I should see that "Dashboard Widget Count" contains "5" for "My Shopping Lists"
    When I click "View All" in "My Shopping Lists Widget" element
    Then I should see that "Page Title" contains "Shopping Lists"
    When I click "Edit" on row "Shopping List 3" in grid
    Then Buyer is on "Shopping List 3" shopping list
    When I click "Shopping List Actions"
    And I click "Rename"
    And I fill "Shopping List Rename Action Form" with:
      | Label | Shopping List 3 updated |
    And I click "Shopping List Action Submit"
    Then I should see "Shopping list has been successfully renamed" flash message and I close it
    And I click "Account Dropdown"
    When I click "Dashboard"
    Then I should see following "My Shopping Lists Grid" grid with exact columns order:
      | Name                    | Items | Subtotal |
      | Shopping List 3 updated | 1     | $10.00   |
      | Shopping List 5         | 1     | $10.00   |
      | Shopping List 4         | 1     | $10.00   |
      | Shopping List 2         | 1     | $10.00   |
      | Shopping List 1         | 1     | $10.00   |
