@feature-BB-24920
@regression
@fixture-OroCommerceBundle:CustomerUserFixture.yml
@fixture-OroCommerceBundle:ProductFixture.yml
@fixture-OroCommerceBundle:OrderFixture.yml

Feature: Verify My Latest Orders Widget on Customer Dashboard
  In order to ensure correct functionality of the My Latest Orders widget in the customer dashboard
  As an administrator
  I should be able to verify its display, item count, and navigation

  Scenario: Validate My Latest Orders Widget and Grid Data
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    When I click "Dashboard"
    Then I should see that "My Latest Orders Widget" contains "My Latest Orders"
    And I should see that "Dashboard Widget Count" contains "5" for "My Latest Orders"
    And I should see following "My Latest Orders Grid" grid with exact columns order:
      | Number | Total  | Status |
      | Order5 | $10.00 | Open   |
      | Order4 | $10.00 | Open   |
      | Order3 | $10.00 | Open   |
      | Order2 | $10.00 | Open   |
      | Order1 | $10.00 | Open   |
    When I click "View All" in "My Latest Orders Widget" element
    Then I should see that "Page Title" contains "Order History"
