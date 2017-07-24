@ticket-BB-10469
@automatically-ticket-tagged
@fixture-OroOrderBundle:order.yml
Feature: Order with Discounts
  In order to manage Orders
  As an administrator
  I need to have ability to use order discounts

  Scenario: Check discount validation for amount and percent types
    Given I login as administrator
    And go to Sales/Orders
    And click edit SimpleOrder in grid
    When I click "Add Discount"
    And I save form
    Then I should see "Order Form" validation errors:
      | Discount Value (first) | This value should not be blank. |
    When I fill "Order Form" with:
      | Discount Type (first) | % |
    And I save form
    Then I should see "Order Form" validation errors:
      | Discount Value (first) | This value should not be blank. |
