@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroPromotionBundle:promotions.yml
@fixture-OroPromotionBundle:shopping_list.yml
Feature: Promotions in Order page
  In order to find out applied discounts in order
  As administrator
  I need to have ability to see applied discounts on order view frontend page
  As a site user
  I need to have ability to see applied discounts on order view page

  Scenario: Logged in as buyer and manager on different window sessions
    Given sessions active:
      | Admin  | first_session  |
      | Buyer  | second_session |
    And I switch to the "Admin" session
    And I login as administrator
    And I switch to the "Buyer" session
    And I signed in as AmandaRCole@example.org on the store frontend

  Scenario: Check that applied discounts are shown on order edit page in promotion section
    Given I operate as the Admin
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
    Then I should see "Promotion discounts will be recalculated after order saving"
    And I should see "line Item Discount Promotion" in "Order Promotions Grid" with following data:
      | Amount  | $5.00 |
    And I should see "order Discount Promotion" in "Order Promotions Grid" with following data:
      | Amount  | $7.50 |
    When I click "Line Items"
    Then I see next line item discounts for backoffice order:
      | SKU  | Row Total Incl Tax | Row Total Excl Tax | Discount |
      | SKU1 | $10.00             | $10.00             | $0.00    |
      | SKU2 | $5.00              | $5.00              | $5.00    |
    And I click "Order Totals"
    And I see next subtotals for "Backend Order":
      | Subtotal | Amount |
      | Subtotal | $20.00 |
      | Discount | $12.50 |
      | Shipping | $3.00  |
# TODO uncomment after fix of BB-10620
#      | Total    | $10.50 |

  Scenario: Check that applied discounts are shown on frontend order view page
    Given I operate as the Buyer
    And click "Orders"
    Then I should see following "Past Orders Grid" grid:
      | Total  |
      | $10.50 |
    And I click "view" on first row in "Past Orders Grid"
    And I show column "Row Total (Discount Amount)" in "Order Line Items Grid" frontend grid
    Then I should see following "Order Line Items Grid" grid:
      | Product                | RTDA  |
      | Product 2 Item #: SKU2 | $5.00 |
      | Product 1 Item #: SKU1 | $0.00 |
    And I see next subtotals for "Order":
      | Subtotal | Amount |
      | Subtotal | $20.00 |
      | Discount | $12.50 |
      | Shipping | $3.00  |
      | Total    | $10.50 |

  Scenario: Change product's quantity and check that after saving without discount recalculation discount amount hasn't changed
    Given I operate as the Admin
    When I fill "Promotion Order Form" with:
      | SKU2ProductQuantity | 3 |
#    Check that line items discounts were reloaded by ajax
    Then I see next line item discounts for backoffice order:
      | SKU  | Row Total Incl Tax | Row Total Excl Tax | Discount |
      | SKU1 | $10.00             | $10.00             | $0.00    |
      | SKU2 | $3.00              | $3.00              | $3.00    |
    And I should see "line Item Discount Promotion" in "Order Promotions Grid" with following data:
      | Amount  | $5.00 |
    And I should see "order Discount Promotion" in "Order Promotions Grid" with following data:
      | Amount  | $7.50 |
    And I click "Order Totals"
    And I see next subtotals for "Backend Order":
      | Subtotal | Amount |
      | Subtotal | $16.00 |
      | Discount | $9.50 |
      | Shipping | $3.00  |
# TODO uncomment after fix of BB-10620
#      | Total    | $9.50 |
    When I save order without discounts recalculation
    And agree that shipping cost may have changed
    And I click "Edit"
    Then I should see "line Item Discount Promotion" in "Order Promotions Grid" with following data:
      | Amount  | $5.00 |
    And I should see "order Discount Promotion" in "Order Promotions Grid" with following data:
      | Amount  | $7.50 |
    And I see next line item discounts for backoffice order:
      | SKU  | Row Total Incl Tax | Row Total Excl Tax | Discount |
      | SKU1 | $10.00             | $10.00             | $0.00    |
      | SKU2 | $1.00              | $1.00              | $5.00    |
    And I click "Order Totals"
    And I see next subtotals for "Backend Order":
      | Subtotal | Amount |
      | Subtotal | $16.00 |
      | Discount | $12.50 |
      | Shipping | $3.00  |
# TODO uncomment after fix of BB-10620
#      | Total    | $6.50 |

  Scenario: Check that applied discounts amounts haven't changed on frontend order view page and right total displayed in orders' grid
    Given I operate as the Buyer
    When click "Orders"
    Then I should see following "Past Orders Grid" grid:
      | Total |
      | $6.50 |
    When I click "view" on first row in "Past Orders Grid"
    And I show column "Row Total (Discount Amount)" in "Order Line Items Grid" frontend grid
    Then I should see following "Order Line Items Grid" grid:
      | Product                | RTDA  |
      | Product 2 Item #: SKU2 | $5.00 |
      | Product 1 Item #: SKU1 | $0.00 |
    And I see next subtotals for "Order":
      | Subtotal | Amount |
      | Subtotal | $16.00 |
      | Discount | $12.50 |
      | Shipping | $3.00  |
      | Total    | $6.50  |

  Scenario: Change products quantity and check that after form saving discount amount has changed
    Given I operate as the Admin
    When I save form
    Then I should see "line Item Discount Promotion" in "Order Promotions Grid" with following data:
      | Amount  | $3.00 |
    And I should see "order Discount Promotion" in "Order Promotions Grid" with following data:
      | Amount  | $6.50 |
    And I see next line item discounts for backoffice order:
      | SKU  | Row Total Incl Tax | Row Total Excl Tax | Discount |
      | SKU1 | $10.00             | $10.00             | $0.00    |
      | SKU2 | $3.00              | $3.00              | $3.00    |
    And I click "Order Totals"
    And I see next subtotals for "Backend Order":
      | Subtotal | Amount |
      | Subtotal | $16.00 |
      | Discount | $9.50  |
      | Shipping | $3.00  |
# TODO uncomment after fix of BB-10620
#      | Total    | $9.50  |

  Scenario: Check that applied discounts amounts have changed on frontend order view page
    Given I operate as the Buyer
    And click "Orders"
    Then I should see following "Past Orders Grid" grid:
      | Total |
      | $9.50 |
    And I click "view" on first row in grid
    When I show column "Row Total (Discount Amount)" in "Order Line Items Grid" frontend grid
    Then I should see following "Order Line Items Grid" grid:
      | Product                | RTDA  |
      | Product 2 Item #: SKU2 | $3.00 |
      | Product 1 Item #: SKU1 | $0.00 |
    And I see next subtotals for "Order":
      | Subtotal | Amount |
      | Subtotal | $16.00 |
      | Discount | $9.50  |
      | Shipping | $3.00  |
      | Total    | $9.50  |
