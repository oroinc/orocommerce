@feature-BB-24920
@regression
@fixture-OroCommerceBundle:CustomerUserFixture.yml
@fixture-OroCommerceBundle:ProductFixture.yml
@fixture-OroCommerceBundle:ShoppingListFixture.yml
@fixture-OroCommerceBundle:CheckoutFixture.yml

Feature: Verify My Checkouts Widget on Customer Dashboard
  In order to ensure correct functionality of the My Checkouts widget in the customer dashboard
  As an administrator
  I should be able to verify its display, item count, and navigation

  Scenario: Validate My Checkouts Widget and Grid Data
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    When I click "Dashboard"
    Then I should see that "My Checkouts Widget" contains "My Checkouts"
    And I should see that "Dashboard Widget Count" contains "5" for "My Checkouts"
    And I should see following "My Checkouts Grid" grid with exact columns order:
      | Started From    | Items | Subtotal |
      | Shopping List 5 | 1     | $50.00   |
      | Shopping List 4 | 1     | $50.00   |
      | Shopping List 3 | 1     | $50.00   |
      | Shopping List 2 | 1     | $50.00   |
      | Shopping List 1 | 1     | $50.00   |
    When I click "View All" in "My Checkouts Widget" element
    Then I should see that "Page Title" contains "Order History"
