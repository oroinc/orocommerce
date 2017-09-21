@ticket-BB-10469
@ticket-BB-10920
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
    And click "Discounts" tab in navigation menu
    When I click "Add Discount"
    And I save form
    Then I should see "Order Form" validation errors:
      | Discount Value (first) | This value should not be blank. |
    When I fill "Order Form" with:
      | Discount Type (first) | % |
    And I save form
    Then I should see "Order Form" validation errors:
      | Discount Value (first) | This value should not be blank. |

  Scenario: Check manual discounts without description functionality
    When I fill "Order Form" with:
      | Discount Value (first) | 2 |
      | Discount Type (first) | USD |
    Then I should see that "Discount Row (first)" contains "$2.00 (4%)"
    And I click "Order Totals"
    And I should see that "Backend Order Subtotals" contains "Discount $2.00"
    When I save form
    Then should see "Order has been saved" flash message
    And click "Discounts" tab in navigation menu
    And I should see that "Discount Row (first)" contains "$2.00 (4%)"
    Then I click "Order Totals"
    And I should see that "Backend Order Subtotals" contains "Discount $2.00"

  Scenario: Check manual discounts with description functionality
    When I fill "Order Form" with:
      | Discount Description (first) | Some |
      | Discount Value (first) | 10 |
      | Discount Type (first) | % |
    Then I should see that "Discount Row (first)" contains "$5.00 (10%)"
    When I click "Order Totals"
    Then I should see that "Backend Order Subtotals" contains "Some (Discount) $5.00"
    When I save form
    And I click "Save" in confirmation dialogue
    Then should see "Order has been saved" flash message
    And click "Discounts" tab in navigation menu
    And I should see that "Discount Row (first)" contains "$5.00 (10%)"
    Then I click "Order Totals"
    And I should see that "Backend Order Subtotals" contains "Some (Discount) $5.00"
