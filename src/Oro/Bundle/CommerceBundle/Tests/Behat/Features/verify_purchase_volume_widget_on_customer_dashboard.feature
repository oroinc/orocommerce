@feature-BB-24920
@regression
@fixture-OroCommerceBundle:CustomerUserFixture.yml
@fixture-OroCommerceBundle:ProductFixture.yml
@fixture-OroCommerceBundle:OrderFixture.yml

Feature: Verify Purchase Volume Widget on Customer Dashboard
  In order to ensure the correct display and accuracy of the Purchase Volume Widget
  As an administrator
  I should be able to validate its tooltip content and value representation

  Scenario: Verify Purchase Volume Tooltip Display
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    When I click "Dashboard"
    And I scroll to "Purchase Volume Widget"
    Then I should see that "Purchase Volume Widget" contains "Purchase Volume"
    And chart tooltip should contain:
      """
      {
        "price": "$50"
      }
      """
