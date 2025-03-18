@feature-BB-24920
@regression
@fixture-OroCommerceBundle:CustomerUserFixture.yml
@fixture-OroCommerceBundle:QuoteFixture.yml

Feature: Verify Open Quotes Widget on Customer Dashboard
  In order to ensure correct functionality of the Open Quotes widget in the customer dashboard
  As an administrator
  I should be able to verify its display, item count, and navigation

  Scenario: Validate Open Quotes Widget and Grid Data
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    When I click "Dashboard"
    Then I should see that "Open Quotes Widget" contains "Open Quotes"
    And I should see that "Dashboard Widget Count" contains "5" for "Open Quotes"
    And I should see following "Open Quotes Grid" grid with exact columns order:
      | Quote # | Po Number | Valid Until        |
      | Quote5  | PO5       | 1/1/5000, 12:00 AM |
      | Quote4  | PO4       | 1/1/5000, 12:00 AM |
      | Quote3  | PO3       | 1/1/5000, 12:00 AM |
      | Quote2  | PO2       | 1/1/5000, 12:00 AM |
      | Quote1  | PO1       | 1/1/5000, 12:00 AM |
    When I click "View All" in "Open Quotes Widget" element
    Then I should see that "Page Title" contains "Quotes"
