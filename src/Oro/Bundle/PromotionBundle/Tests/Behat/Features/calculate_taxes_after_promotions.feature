@feature-BB-8416
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPromotionBundle:taxes-and-promotions.yml

Feature: Calculate taxes after promotions
  In order to comply with tax laws when giving promotional discounts
  As an Administrator
  I want to specify whether the promotion should be treated differently for tax purposes

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    When I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Taxation/Tax Calculation" on configuration sidebar
    And uncheck "Use default" for "Use as Base by Default" field
    And uncheck "Use default" for "Origin Address" field
    And I fill "Tax Calculation Form" with:
      | Use As Base By Default | Origin        |
      | Origin Country         | United States |
      | Origin Region          | Florida       |
      | Origin Zip Code        | 90001         |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Enable Calculate taxes after promotions option
    Given the "Calculate Taxes After Promotions" checkbox should not be checked
    When uncheck "Use default" for "Calculate Taxes After Promotions" field
    And I check "Calculate Taxes After Promotions"
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Create order
    When I go to Sales/Orders
    And I click "Create Order"
    And click "Add Product"
    And click "Add Product"
    And fill "Order Form" with:
      | Customer User | Amanda Cole |
      | PO Number     | PONumber1   |
      | Product       | SKU1        |
      | Price         | 2           |
      | Quantity      | 5           |
      | Product2      | SKU2        |
      | Price2        | 2           |
      | Quantity2     | 5           |
    And I click "Calculate Shipping"
    And I click "Shipping Method Flat Rate Radio Button"
    Then I should see "Subtotal $20.00"
    And I should see "Discount -$16.50"
    And I should see "Shipping $3.00"
    And I should see "Shipping Discount -$1.00"
    And I should see "Tax $0.24"
    And I should see "Total $5.74"
    When I save and close form
    Then I should see "Order has been saved" flash message

  Scenario: Taxes correctly calculated on Checkout pages
    Given I operate as the Buyer
    When I signed in as AmandaRCole@example.org on the store frontend
    And I open page with shopping list List 1
    And I click "Create Order"
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    Then I should see "Subtotal $20.00"
    And I should see "Discount -$16.50"
    And I should see "Shipping $3.00"
    And I should see "Shipping Discount -$1.00"
    And I should see "Tax $0.24"
    And I should see "Total $5.74"
    When I fill form with:
      | PO Number | PONumber2 |
    And I click "Delete this shopping list after submitting order"
    And I wait "Submit Order" button
    And I focus on "Submit Order"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Taxes correctly displayed on storefront Order view page
    When I click "click here to review"
    Then I should see "Subtotal $20.00"
    And I should see "Discount -$16.50"
    And I should see "Shipping $3.00"
    And I should see "Shipping Discount -$1.00"
    And I should see "Tax $0.24"
    And I should see "Total $5.74"
    When I click on "FrontendGridColumnManagerButton"
    And I click "Select All"
    And I click on empty space
    And I hide column "Taxes" in "Order Line Items Grid" frontend grid
    Then I should see following "Order Line Items Grid" grid:
      | Product                | UPIT  | UPET  | UPTA  |  RTIT | RTET  | RTTA  | RTDA  | RTADIT | RTADET |
      | Product 1 Item #: SKU1 | $0.51 | $0.47 | $0.05 | $2.59 | $2.35 | $0.24 | $0.00 | $2.59  | $2.35  |
      | Product 2 Item #: SKU2 | $0.23 | $0.23 | $0.00 | $1.15 | $1.15 | $0.00 | $5.00 | $0.00  | $0.00  |

  Scenario: Enable Product Prices Include Tax option
    Given I proceed as the Admin
    When I go to System/Configuration
    And I follow "Commerce/Taxation/Tax Calculation" on configuration sidebar
    And uncheck "Use default" for "Product Prices Include Tax" field
    And I check "Product Prices Include Tax"
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Create order
    When I go to Sales/Orders
    And I click "Create Order"
    And click "Add Product"
    And click "Add Product"
    And fill "Order Form" with:
      | Customer User | Amanda Cole |
      | PO Number     | PONumber3   |
      | Product       | SKU1        |
      | Price         | 2           |
      | Quantity      | 5           |
      | Product2      | SKU2        |
      | Price2        | 2           |
      | Quantity2     | 5           |
    And I click "Calculate Shipping"
    And I click "Shipping Method Flat Rate Radio Button"
    Then I should see "Subtotal $20.00"
    And I should see "Discount -$16.50"
    And I should see "Shipping $3.00"
    And I should see "Shipping Discount -$1.00"
    And I should see "Tax $0.21"
    And I should see "Total $5.50"
    When I save and close form
    Then I should see "Order has been saved" flash message

  Scenario: Taxes correctly calculated on Checkout pages
    Given I operate as the Buyer
    When I open page with shopping list List 1
    And I click "Create Order"
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    And I click "Continue"
    Then I should see "Subtotal $20.00"
    And I should see "Discount -$16.50"
    And I should see "Shipping $3.00"
    And I should see "Shipping Discount -$1.00"
    And I should see "Tax $0.21"
    And I should see "Total $5.50"
    When I fill form with:
      | PO Number | PONumber4 |
    And I click "Delete this shopping list after submitting order"
    And I wait "Submit Order" button
    And I focus on "Submit Order"
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Taxes correctly displayed on storefront Order view page
    When I click "click here to review"
    Then I should see "Subtotal $20.00"
    And I should see "Discount -$16.50"
    And I should see "Shipping $3.00"
    And I should see "Shipping Discount -$1.00"
    And I should see "Tax $0.21"
    And I should see "Total $5.50"
    When I click on "FrontendGridColumnManagerButton"
    And I click "Select All"
    And I click on empty space
    And I hide column "Taxes" in "Order Line Items Grid" frontend grid
    Then I should see following "Order Line Items Grid" grid:
      | Product                | UPIT  | UPET  | UPTA  |  RTIT | RTET  | RTTA  | RTDA  | RTADIT | RTADET |
      | Product 1 Item #: SKU1 | $0.47 | $0.42 | $0.04 | $2.35 | $2.14 | $0.21 | $0.00 | $2.35  | $2.14  |
      | Product 2 Item #: SKU2 | $0.23 | $0.23 | $0.00 | $1.15 | $1.15 | $0.00 | $5.00 | $0.00  | $0.00  |

  Scenario: Check Orders grid
    Given I proceed as the Admin
    When I go to Sales/Orders
    Then I should see following grid:
      | Order Number | PO Number | Total |
      | 1            | PONumber1 | $5.74 |
      | 2            | PONumber2 | $5.74 |
      | 3            | PONumber3 | $5.50 |
      | 4            | PONumber4 | $5.50 |

  Scenario Outline: Taxes correctly displayed on Backoffice Order view page
    Given I go to Sales/Orders
    When click view "<PONumber>" in grid
    And I click "Line Items"
    And I show all columns in "Backend Order Line Items Grid" except Taxes
    Then I should see following "Backend Order Line Items Grid" grid:
      | SKU  | Product   | UPIT        | UPET        | UPTA        | RTIT        | RTET        | RTTA        | RTDA        | RTADIT        | RTADET        |
      | SKU1 | Product 1 | <UPIT_SKU1> | <UPET_SKU1> | <UPTA_SKU1> | <RTIT_SKU1> | <RTET_SKU1> | <RTTA_SKU1> | <RTDA_SKU1> | <RTADIT_SKU1> | <RTADET_SKU1> |
      | SKU2 | Product 2 | <UPIT_SKU2> | <UPET_SKU2> | <UPTA_SKU2> | <RTIT_SKU2> | <RTET_SKU2> | <RTTA_SKU2> | <RTDA_SKU2> | <RTADIT_SKU2> | <RTADET_SKU2> |
    Examples:
      | PONumber  | UPIT_SKU1 | UPET_SKU1 | UPTA_SKU1 | RTIT_SKU1 | RTET_SKU1 | RTTA_SKU1 | RTDA_SKU1 | RTADIT_SKU1 | RTADET_SKU1 | UPIT_SKU2 | UPET_SKU2 | UPTA_SKU2 | RTIT_SKU2 | RTET_SKU2 | RTTA_SKU2 | RTDA_SKU2 | RTADIT_SKU2 | RTADET_SKU2 |
      | PONumber1 | $0.51     | $0.47     | $0.05     | $2.59     | $2.35     | $0.24     | $0.00     | $2.59       | $2.35       | $0.23     | $0.23     | $0.00     | $1.15     | $1.15     | $0.00     | $5.00     | $0.00       | $0.00       |
      | PONumber2 | $0.51     | $0.47     | $0.05     | $2.59     | $2.35     | $0.24     | $0.00     | $2.59       | $2.35       | $0.23     | $0.23     | $0.00     | $1.15     | $1.15     | $0.00     | $5.00     | $0.00       | $0.00       |
      | PONumber3 | $0.47     | $0.42     | $0.04     | $2.35     | $2.14     | $0.21     | $0.00     | $2.35       | $2.14       | $0.23     | $0.23     | $0.00     | $1.15     | $1.15     | $0.00     | $5.00     | $0.00       | $0.00       |
      | PONumber4 | $0.47     | $0.42     | $0.04     | $2.35     | $2.14     | $0.21     | $0.00     | $2.35       | $2.14       | $0.23     | $0.23     | $0.00     | $1.15     | $1.15     | $0.00     | $5.00     | $0.00       | $0.00       |

  Scenario: Disable Product Prices Include Tax option
    When I go to System/Configuration
    And I follow "Commerce/Taxation/Tax Calculation" on configuration sidebar
    And I uncheck "Product Prices Include Tax"
    And check "Use default" for "Product Prices Include Tax" field
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Taxes recalculated after using coupons
    Given I go to Sales/Orders
    When click view "PONumber1" in grid
    And click "Add Coupon Code"
    And type "coupon50p" in "Coupon Code"
    Then should see a "Highlighted Suggestion" element
    When click on "Highlighted Suggestion"
    And I click "Add" in modal window
    And click "Apply" in modal window
    Then I should see "Tax $0.12"
    And I should see "Total $3.87"

  Scenario: Taxes recalculated when coupon was deactivated
    When I click "Edit"
    And I click "Fifth Promotion Change Active Button"
    Then I should see "Tax $0.24"
    And I should see "Total $5.74"
