@feature-BB-24920
@regression
@fixture-OroCommerceBundle:CustomerUserFixture.yml
@fixture-OroCommerceBundle:RfqFixture.yml

Feature: Customer Dashboard Verification
  In order to ensure correct functionality of the new customer dashboard page
  As an administrator
  I should be able to verify its accessibility, UI elements, and key features

  Scenario: Validate Requests for Quote Widget and Grid
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    When I click "Dashboard"
    Then I should see that "Requests For Quote Widget" contains "Requests For Quote"
    And I should see that "Dashboard Widget Count" contains "5" for "Requests For Quote"
    And I should see following "Request For Quote Grid" grid with exact columns order:
      | Rfq # | Po Number | Status    |
      | 5     | PO5       | Submitted |
      | 4     | PO4       | Submitted |
      | 3     | PO3       | Submitted |
      | 2     | PO2       | Submitted |
      | 1     | PO1       | Submitted |
    When I click "View All" in "Requests For Quote Widget" element
    Then I should see that "Page Title" contains "Requests For Quote"
