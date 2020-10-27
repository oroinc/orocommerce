@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:promotions.yml
@fixture-OroPromotionBundle:shopping_list.yml
Feature: Promotions on Order page
  In order to find out applied discounts in order
  As administrator
  I need to have ability to see applied discounts on order view frontend page
  As a site user
  I need to have ability to see applied discounts on order view page

  Scenario: Check that applied discounts are shown on order edit page in promotion section
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I login as administrator and use in "second_session" as "Admin"
    And I disable inventory management
    And I proceed as the Buyer
    And I do the order through completion, and should be on order view page
    And I operate as the Admin
    When I go to Marketing / Promotions / Promotions
    Then I should see following records in grid:
      | line Item Discount Promotion |
      | order Discount Promotion     |
    When I go to Sales / Orders
    And I click "edit" on first row in grid
    Then I should see next rows in "Promotions" table
      | Promotion                    | Discount |
      | line Item Discount Promotion | -$5.00   |
      | shipping Discount Promotion  | -$1.00   |
      | order Discount Promotion     | -$7.50   |
    When I click "Line Items"
    Then I see next line item discounts for backoffice order:
      | SKU  | Row Total Incl Tax | Row Total Excl Tax | Discount |
      | SKU1 | $10.00             | $10.00             | $0.00    |
      | SKU2 | $5.00              | $5.00              | $5.00    |
    And I click "Order Totals"
    And I see next subtotals for "Backend Order":
      | Subtotal          | Amount  |
      | Subtotal          | $20.00  |
      | Discount          | -$12.50 |
      | Shipping          | $3.00   |
      | Shipping Discount | -$1.00  |
      | Total             | $9.50   |

  Scenario: Check that applied discounts are shown on frontend order view page
    Given I operate as the Buyer
    And click "Orders"
    Then I should see following "Past Orders Grid" grid:
      | Total |
      | $9.50 |
    And I click "view" on first row in "Past Orders Grid"
    And I show column "Row Total (Discount Amount)" in "Order Line Items Grid" frontend grid
    Then I should see following "Order Line Items Grid" grid:
      | Product                | RTDA  |
      | Product 1 Item #: SKU1 | $0.00 |
      | Product 2 Item #: SKU2 | $5.00 |
    And I see next subtotals for "Order":
      | Subtotal          | Amount  |
      | Subtotal          | $20.00  |
      | Discount          | -$12.50 |
      | Shipping          | $3.00   |
      | Shipping Discount | -$1.00  |
      | Total             | $9.50   |

  Scenario: Change product's quantity and check that discount amount changed accordingly, instantly and after save
    Given I operate as the Admin
    And I click "Line Items"
    When I fill "Promotion Order Form" with:
      | SKU2ProductQuantity | 3 |
    Then I see next line item discounts for backoffice order:
      | SKU  | Row Total Incl Tax | Row Total Excl Tax | Discount |
      | SKU1 | $10.00             | $10.00             | $0.00    |
      | SKU2 | $3.00              | $3.00              | $3.00    |
    And I should see next rows in "Promotions" table
      | Promotion                    | Discount |
      | line Item Discount Promotion | -$3.00   |
      | shipping Discount Promotion  | -$1.00   |
      | order Discount Promotion     | -$6.50   |
    And I click "Order Totals"
    And I see next subtotals for "Backend Order":
      | Subtotal          | Amount |
      | Subtotal          | $16.00 |
      | Discount          | -$9.50 |
      | Shipping          | $3.00  |
      | Shipping Discount | -$1.00 |
      | Total             | $8.50  |
    When I save form
    And agree that shipping cost may have changed
    Then I should see "Order has been saved" flash message
    Then I should see next rows in "Promotions" table
      | Promotion                    | Discount |
      | line Item Discount Promotion | -$3.00   |
      | shipping Discount Promotion  | -$1.00   |
      | order Discount Promotion     | -$6.50   |
    And I see next line item discounts for backoffice order:
      | SKU  | Row Total Incl Tax | Row Total Excl Tax | Discount |
      | SKU1 | $10.00             | $10.00             | $0.00    |
      | SKU2 | $3.00              | $3.00              | $3.00    |
    And I click "Order Totals"
    And I see next subtotals for "Backend Order":
      | Subtotal          | Amount |
      | Subtotal          | $16.00 |
      | Discount          | -$9.50 |
      | Shipping          | $3.00  |
      | Shipping Discount | -$1.00 |
      | Total             | $8.50  |

  Scenario: Deactivate automatic promotion
    Given I click "Promotions and Discounts"
    And I click "Deactivate" on row "order Discount Promotion Order Total" in "Promotions"
    Then I should see next rows in "Promotions" table
      | Promotion                    | Status   |
      | line Item Discount Promotion | Active   |
      | shipping Discount Promotion  | Active   |
      | order Discount Promotion     | Inactive |
    And see next subtotals for "Backend Order":
      | Subtotal          | Amount |
      | Subtotal          | $16.00 |
      | Discount          | -$3.00 |
      | Shipping          | $3.00  |
      | Shipping Discount | -$1.00 |
      | Total             | $15.00 |
    When I save form
    And agree that shipping cost may have changed
    Then I should see "Order has been saved" flash message
    And should see next rows in "Promotions" table
      | Promotion                    | Status   |
      | line Item Discount Promotion | Active   |
      | shipping Discount Promotion  | Active   |
      | order Discount Promotion     | Inactive |
    And see next subtotals for "Backend Order":
      | Subtotal          | Amount |
      | Subtotal          | $16.00 |
      | Discount          | -$3.00 |
      | Shipping          | $3.00  |
      | Shipping Discount | -$1.00 |
      | Total             | $15.00 |

  Scenario: Delete automatic promotion
    Given I click "Promotions and Discounts"
    And I click "Remove" on row "order Discount Promotion Order Total" in "Promotions"
    Then I should see next rows in "Promotions" table
      | Promotion                    | Status |
      | line Item Discount Promotion | Active |
      | shipping Discount Promotion  | Active |
    And see next subtotals for "Backend Order":
      | Subtotal          | Amount |
      | Subtotal          | $16.00 |
      | Discount          | -$3.00 |
      | Shipping          | $3.00  |
      | Shipping Discount | -$1.00 |
      | Total             | $15.00 |
    When I save form
    And agree that shipping cost may have changed
    Then I should see "Order has been saved" flash message
    And should see next rows in "Promotions" table
      | Promotion                    | Status |
      | line Item Discount Promotion | Active |
      | shipping Discount Promotion  | Active |
    And see next subtotals for "Backend Order":
      | Subtotal          | Amount |
      | Subtotal          | $16.00 |
      | Discount          | -$3.00 |
      | Shipping          | $3.00  |
      | Shipping Discount | -$1.00 |
      | Total             | $15.00 |
