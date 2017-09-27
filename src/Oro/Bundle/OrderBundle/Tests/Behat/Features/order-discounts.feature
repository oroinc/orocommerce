@ticket-BB-12071
@fixture-OroOrderBundle:order.yml
Feature: Discounts for Order
  In order to give simple discounts for Orders
  As an administrator
  I need to have ability to manage Order discounts

  Scenario: Calculate order totals
    Given I login as administrator
    And go to Sales/Orders
    And click edit SimpleOrder in grid
    And save form

  Scenario: Add special discount from Order view page
    Given I go to Sales/Orders
    And click view SimpleOrder in grid
    And click "Add Special Discount"
    And I fill "Order Discount Form" with:
      | Value       |      2 |
      | Description | Amount |
    Then I should see "$2.00 (4.00%)"
    And I click "Apply"

    When I click "Add Special Discount"
    And I fill "Order Discount Form" with:
      | Description | <script>alert(1)</script> |
    And I fill in Discount Amount field with "3"
    Then I should see "$3.00 (6.00%)"
    And I click "Apply"

    And I should see next rows in "Discounts" table
      | Description               | Discount |
      | <script>alert(1)</script> | -$3.00   |
      | Amount                    | -$2.00   |
    And I see next subtotals for "Backend Order":
      | Subtotal                             | Amount |
      | Subtotal                             | $50.00 |
      | <script>alert(1)</script> (Discount) | -$3.00 |
      | Amount (Discount)                    | -$2.00 |
      | Total                                | $45.00 |

  Scenario: Add special discount from Order edit page
    Given go to Sales/Orders
    And click edit SimpleOrder in grid
    And click "Promotions and Discounts"
    And click "Add Special Discount"
    And I fill "Order Discount Form" with:
      | Type        | %       |
      | Value       | 1       |
      | Description | Percent |
    Then I should see "$0.50 (1%)"
    And I click "Apply" in modal window
    Then I should see next rows in "Discounts" table
      | Description               | Discount |
      | <script>alert(1)</script> | -$3.00   |
      | Amount                    | -$2.00   |
      | Percent                   | -$0.50   |
    And I see next subtotals for "Backend Order":
      | Subtotal                             | Amount |
      | Subtotal                             | $50.00 |
      | <script>alert(1)</script> (Discount) | -$3.00 |
      | Amount (Discount)                    | -$2.00 |
      | Percent (Discount)                   | -$0.50 |
      | Total                                | $44.50 |

  Scenario: Edit special discount
    When I click "Edit" on row "Percent" in "Discounts"
    And I fill "Order Discount Form" with:
      | Value       | 2       |
      | Description | Percent |
    Then I should see "$1.00 (2%)"
    And I click "Apply" in modal window
    When I save form
    Then I should see "Review Shipping Cost"
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    And I should see next rows in "Discounts" table
      | Description               | Discount |
      | Percent                   | -$1.00   |
      | <script>alert(1)</script> | -$3.00   |
      | Amount                    | -$2.00   |
    And I see next subtotals for "Backend Order":
      | Subtotal                             | Amount |
      | Subtotal                             | $50.00 |
      | <script>alert(1)</script> (Discount) | -$3.00 |
      | Amount (Discount)                    | -$2.00 |
      | Percent (Discount)                   | -$1.00 |
      | Total                                | $44.00 |

  Scenario: Remove special discount
    When I click "Remove" on row "Percent" in "Discounts"
    And I should see next rows in "Discounts" table
      | Description               | Discount |
      | <script>alert(1)</script> | -$3.00   |
      | Amount                    | -$2.00   |
    When I save form
    Then I should see "Review Shipping Cost"
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    And I should see next rows in "Discounts" table
      | Description               | Discount |
      | <script>alert(1)</script> | -$3.00   |
      | Amount                    | -$2.00   |
    And I see next subtotals for "Backend Order":
      | Subtotal                             | Amount |
      | Subtotal                             | $50.00 |
      | <script>alert(1)</script> (Discount) | -$3.00 |
      | Amount (Discount)                    | -$2.00 |
      | Total                                | $45.00 |

  Scenario: Check that discount's amount is less than subtotal
    When I click "Add Special Discount"
    And I fill "Order Discount Form" with:
      | Value       | 51 |
      | Description | Amount is greater than remaining subtotal |
    Then I should see "This value should be 50 or less."
    When I click "Cancel"
    Then I should see next rows in "Discounts" table
      | Description               | Discount |
      | <script>alert(1)</script> | -$3.00   |
      | Amount                    | -$2.00   |
    And I see next subtotals for "Backend Order":
      | Subtotal                             | Amount |
      | Subtotal                             | $50.00 |
      | <script>alert(1)</script> (Discount) | -$3.00 |
      | Amount (Discount)                    | -$2.00 |
      | Total                                | $45.00 |

  Scenario: Check discounts' total sum is less than subtotal
    When I click "Add Special Discount"
    And I fill "Order Discount Form" with:
      | Value       | 50               |
      | Description | Exceeding amount |
    When I click "Apply"
    Then I should see "The sum of all discounts cannot exceed the order grand total amount."
    And I click "Promotions and Discounts"
    Then I should see next rows in "Discounts" table
      | Description               | Discount |
      | <script>alert(1)</script> | -$3.00   |
      | Amount                    | -$2.00   |
      | Exceeding amount          | -$50.00  |
    And I see next subtotals for "Backend Order":
      | Subtotal                             | Amount  |
      | Subtotal                             | $50.00  |
      | <script>alert(1)</script> (Discount) | -$3.00  |
      | Amount (Discount)                    | -$2.00  |
      | Exceeding amount (Discount)          | -$50.00 |
      | Total                                | $0.00   |

  Scenario: Check discount not blank validation for amount type
    When I click "Add Special Discount"
    And I fill "Order Discount Form" with:
      | Value | |
    And I click "Apply" in modal window
    Then I should see "This value should not be blank"
    When I fill "Order Discount Form" with:
      | Value | 1 |
    Then I should not see "This value should not be blank"

  Scenario: Check discount not blank validation for percent type
    When I fill "Order Discount Form" with:
      | Value |   |
      | Type  | % |
    And I click "Apply" in modal window
    Then I should see "This value should not be blank"
    When I fill "Order Discount Form" with:
      | Value | 50 |
    Then I should not see "This value should not be blank"
