@feature-BB-26023-enabled
@regression
@fixture-OroOrderBundle:OrderWithPromotion.yml

Feature: Order edit draft session preserves changes - Order Draft Edit Mode

  Scenario: Feature Background
    Given sessions active:
      | Admin  | first_session  |
      | Admin1 | second_session |

  Scenario: Enable Order Draft Edit Mode
    Given I set configuration property "oro_order.enable_order_draft_edit_mode" to "1"

  Scenario: Check Order information
    Given I proceed as the Admin
    And I login as administrator
    When I go to Sales/ Orders
    And I click edit SimpleOrder in grid
    Then "Order Form" must contains values:
      | Customer               | first customer  |
      | Customer User          | Amanda Cole     |
      | PO Number              | ORD1            |
      | Do Not Ship Later Than | Dec 31, 2027    |
      | Customer Notes         | Customer Notes  |
    And Order Billing Address Select field is empty
    And Order Shipping Address Select field is empty

    When I sort "Order Line Item Draft Grid" by "SKU"
    Then I should see following "Order Line Item Draft Grid" grid:
      | SKU  | Product         | Quantity | Price     | Ship By     |
      | SKU1 | Laptop Computer | 2 items  | $1,299.99 | Jan 1, 2010 |
      | SKU2 | Wireless Mouse  | 5 sets   | $29.99    | Feb 2, 2010 |
      | SKU3 | USB Keyboard    | 3 items  | $59.99    | Mar 3, 2010 |
      | SKU4 | Monitor 24 inch | 1 item   | $349.99   | Apr 4, 2010 |
      | SKU5 | HDMI Cable      | 10 items | $15.99    | May 5, 2010 |
    And I see next subtotals for "Backend Order":
      | Subtotal | Amount    |
      | Subtotal | $3,439.79 |
      | Total    | $3,439.79 |

  Scenario: Fill Order form with new data
    When I fill "Order Form" with:
      | Customer               | Wholesaler B           |
      | Customer User          | Marlene Bradley        |
      | PO Number              | ORD_UPDATED            |
      | Do Not Ship Later Than | <DateTime:2018-04-02>  |
      | Customer Notes         | Updated Customer Notes |
    And I fill "Order Form" with:
      | Shipping Address First name  | Name           |
      | Shipping Address Last name   | Last name      |
      | Shipping Address Country     | United States  |
      | Shipping Address Street      | 801 Scenic Hwy |
      | Shipping Address City        | Haines City    |
      | Shipping Address State       | Florida        |
      | Shipping Address Postal Code | 33844          |
    And I fill "Order Form" with:
      | Billing Address First name  | Name           |
      | Billing Address Last name   | Last name      |
      | Billing Address Country     | United States  |
      | Billing Address Street      | 801 Scenic Hwy |
      | Billing Address City        | Haines City    |
      | Billing Address State       | Florida        |
      | Billing Address Postal Code | 33844          |

  Scenario: Fill line items with new data
    When I click "Line Items"
    And I fill "Order Line Item Draft Create Form" with:
      | Product   | SKU1 |
      | Quantity  | 1    |
      | Price     | 10   |
    And I click "Add Product"
    Then number of records should be 6

    When I click Edit "SKU2" in grid
    And I fill "Order Line Item Draft Edit Form" with:
      | Quantity  | 1 |
    Then I click on "Order Line Item Draft Edit Form Save Button"

    When I click "Line Items"
    And I click delete "SKU4" in grid
    Then I click "Yes, Delete" in confirmation dialogue

    When I click Edit "SKU5" in grid
    And I click on "Order Line Item Draft Edit Form Delete Button"
    Then I click "Yes, Delete" in confirmation dialogue

  Scenario: Add special discount and coupon to Order
    When I click "Discounts"
    And I click "Add Special Discount"
    And I fill "Order Discount Form" with:
      | Type | % |
    And I type "10" in "Discount Value"
    And I type "Amount" in "Discount Description"
    Then I should see "$281.99 (10%)"
    And I click "Apply" in modal window

    When I click "Add Coupon Code"
    And I type "order-coupon-1" in "Coupon Code"
    Then I should see a "Highlighted Suggestion" element
    When I click on "Highlighted Suggestion"
    And I click "Add" in modal window
    Then I should see next rows in "Added Coupons" table
      | Coupon Code    | Promotion              | Discount Value |
      | order-coupon-1 | Order Coupon Promotion | 10%            |
    When I click "Apply" in modal window
    Then I see next subtotals for "Backend Order":
      | Subtotal          | Amount    |
      | Subtotal          | $2,819.94 |
      | Amount (Discount) | -$281.99  |
      | Discount          | -$281.99  |
      | Total             | $2,255.96 |

  Scenario: Verify data persists after page reload
    When I reload the page
    And I accept alert
    Then I should see following "Order Line Item Draft Grid" grid:
      | SKU  | Product         | Quantity | Price     | Ship By     |
      | SKU1 | Laptop Computer | 2 items  | $1,299.99 | Jan 1, 2010 |
      | SKU1 | Laptop Computer | 1 item   | $10.00    |             |
      | SKU2 | Wireless Mouse  | 1 set    | $29.99    | Feb 2, 2010 |
      | SKU3 | USB Keyboard    | 3 items  | $59.99    | Mar 3, 2010 |
    And I see next subtotals for "Backend Order":
      | Subtotal          | Amount    |
      | Subtotal          | $2,819.94 |
      | Amount (Discount) | -$281.99  |
      | Discount          | -$281.99  |
      | Total             | $2,255.96 |
    And "Order Form" must contains values:
      | Customer               | Wholesaler B           |
      | Customer User          | Marlene Bradley        |
      | PO Number              | ORD_UPDATED            |
      | Do Not Ship Later Than | Apr 2, 2018            |
      | Customer Notes         | Updated Customer Notes |
    And "Order Form" must contains values:
      | Shipping Address First name  | Name           |
      | Shipping Address Last name   | Last name      |
      | Shipping Address Country     | United States  |
      | Shipping Address Street      | 801 Scenic Hwy |
      | Shipping Address City        | Haines City    |
      | Shipping Address State       | Florida        |
      | Shipping Address Postal Code | 33844          |
      | Billing Address First name   | Name           |
      | Billing Address Last name    | Last name      |
      | Billing Address Country      | United States  |
      | Billing Address Street       | 801 Scenic Hwy |
      | Billing Address City         | Haines City    |
      | Billing Address State        | Florida        |
      | Billing Address Postal Code  | 33844          |

  Scenario: Ensure that unsaved changes are not displayed when opening a new editing session
    Given I proceed as the Admin1
    And I login as administrator
    When I go to Sales/ Orders
    And I click edit SimpleOrder in grid
    Then "Order Form" must contains values:
      | Customer               | first customer  |
      | Customer User          | Amanda Cole     |
      | PO Number              | ORD1            |
      | Do Not Ship Later Than | Dec 31, 2027    |
      | Customer Notes         | Customer Notes  |
    And Order Billing Address Select field is empty
    And Order Shipping Address Select field is empty
    When I sort "Order Line Item Draft Grid" by "SKU"
    Then I should see following "Order Line Item Draft Grid" grid:
      | SKU  | Product         | Quantity | Price     | Ship By     |
      | SKU1 | Laptop Computer | 2 items  | $1,299.99 | Jan 1, 2010 |
      | SKU2 | Wireless Mouse  | 5 sets   | $29.99    | Feb 2, 2010 |
      | SKU3 | USB Keyboard    | 3 items  | $59.99    | Mar 3, 2010 |
      | SKU4 | Monitor 24 inch | 1 item   | $349.99   | Apr 4, 2010 |
      | SKU5 | HDMI Cable      | 10 items | $15.99    | May 5, 2010 |
    And I see next subtotals for "Backend Order":
      | Subtotal | Amount    |
      | Subtotal | $3,439.79 |
      | Total    | $3,439.79 |

  Scenario: Check that the saved changes are displayed
    Given I proceed as the Admin
    When save form
    Then I should see "Order has been saved" flash message
    And I should see following "Order Line Item Draft Grid" grid:
      | SKU  | Product         | Quantity | Price     | Ship By     |
      | SKU1 | Laptop Computer | 2 items  | $1,299.99 | Jan 1, 2010 |
      | SKU1 | Laptop Computer | 1 item   | $10.00    |             |
      | SKU2 | Wireless Mouse  | 1 set    | $29.99    | Feb 2, 2010 |
      | SKU3 | USB Keyboard    | 3 items  | $59.99    | Mar 3, 2010 |
    And I see next subtotals for "Backend Order":
      | Subtotal          | Amount    |
      | Subtotal          | $2,819.94 |
      | Amount (Discount) | -$281.99  |
      | Discount          | -$281.99  |
      | Total             | $2,255.96 |
