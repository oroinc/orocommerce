@feature-BB-21128
@feature-BB-23538
@ticket-BB-23545
@ticket-BB-23546
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroTaxBundle:product-kit/product_kit_with_taxes_and_promotion.yml

Feature: Create Order with product kits and taxes
  As a user of the back-office
  I should see the correctly calculated taxes and discounts for orders with product kits

  Scenario: Feature Background
    Given I login as administrator
    And I enable configuration options:
      | oro_tax.tax_enable |
    And I change configuration options:
      | oro_tax.use_as_base_by_default | destination |

  Scenario: Add a product kit line item
    Given go to Sales / Orders
    And click "Create Order"
    And I click "Add Product"
    When I fill "Order Form" with:
      | Customer      | Customer1      |
      | Customer User | Amanda Cole    |
      | Product       | product-kit-01 |
    Then I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $137.04   | $124.69   | $12.35     |
      | Row Total  | $137.04   | $124.69   | $12.35     |
    And I should see next rows in "Backend Order First Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $123.46        | $12.35     |
    And I see next line item discounts for backoffice order:
      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount            |
      | product-kit-01 | $124.571           | $112.221           | $12.468999999999999 |
    And I see next subtotals for "Backend Order":
      | Subtotal | $124.69 |
      | Discount | -$12.47 |
      | Tax      | $12.35  |
      | Total    | $124.57 |
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $0.00     | $0.00     | $0.00      |
      | Total    | $137.04   | $124.69   | $12.35     |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $123.46        | $12.35     |
    And the "Price" field should be readonly in form "Order Form"

  Scenario: Change product kit line item quantity
    When I fill "Order Form" with:
      | Quantity | 2 |
    Then I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $137.04   | $124.69   | $12.35     |
      | Row Total  | $274.07   | $249.38   | $24.69     |
    And I should see next rows in "Backend Order First Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $246.92        | $24.69     |
    And I see next line item discounts for backoffice order:
      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount            |
      | product-kit-01 | $249.132           | $224.442           | $24.937999999999999 |
    And I see next subtotals for "Backend Order":
      | Subtotal | $249.38 |
      | Discount | -$24.94 |
      | Tax      | $24.69  |
      | Total    | $249.13 |
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $0.00     | $0.00     | $0.00      |
      | Total    | $274.07   | $249.38   | $24.69     |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $246.92        | $24.69     |
    And the "Price" field should be readonly in form "Order Form"

  Scenario: Add product kit item line item product
    When I fill "Order Form" with:
      | ProductKitItem1Product | simple-product-03 - Simple Product 03 |
    Then I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $141.11   | $128.39   | $12.72     |
      | Row Total  | $282.21   | $256.78   | $25.43     |
    And I should see next rows in "Backend Order First Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $254.32        | $25.43     |
    And I see next line item discounts for backoffice order:
      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount            |
      | product-kit-01 | $256.532           | $231.102           | $25.677999999999997 |
    And I see next subtotals for "Backend Order":
      | Subtotal | $256.78 |
      | Discount | -$25.68 |
      | Tax      | $25.43  |
      | Total    | $256.53 |
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $0.00     | $0.00     | $0.00      |
      | Total    | $282.21   | $256.78   | $25.43     |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $254.32        | $25.43     |
    And the "Price" field should be readonly in form "Order Form"

  Scenario: Change product kit item line item product
    When I fill "Order Form" with:
      | ProductKitItem2Product | simple-product-02 - Simple Product 02 |
    Then I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $142.59   | $129.63   | $12.96     |
      | Row Total  | $285.19   | $259.26   | $25.93     |
    And I should see next rows in "Backend Order First Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $259.26        | $25.93     |
    And I see next line item discounts for backoffice order:
      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount            |
      | product-kit-01 | $259.264           | $233.334           | $25.925999999999998 |
    And I see next subtotals for "Backend Order":
      | Subtotal | $259.26 |
      | Discount | -$25.93 |
      | Tax      | $25.93  |
      | Total    | $259.26 |
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $0.00     | $0.00     | $0.00      |
      | Total    | $285.19   | $259.26   | $25.93     |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $259.26        | $25.93     |
    And the "Price" field should be readonly in form "Order Form"

  Scenario: Change product kit item line item quantity
    When I fill "Order Form" with:
      | ProductKitItem1Quantity | 2 |
      | ProductKitItem2Quantity | 3 |
    Then I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $152.10   | $138.27   | $13.83     |
      | Row Total  | $304.19   | $276.54   | $27.65     |
    And I should see next rows in "Backend Order First Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $276.54        | $27.65     |
    And I see next line item discounts for backoffice order:
      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount            |
      | product-kit-01 | $276.536           | $248.886           | $27.654000000000003 |
    And I see next subtotals for "Backend Order":
      | Subtotal | $276.54 |
      | Discount | -$27.65 |
      | Tax      | $27.65  |
      | Total    | $276.54 |
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $0.00     | $0.00     | $0.00      |
      | Total    | $304.19   | $276.54   | $27.65     |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $276.54        | $27.65     |
    And the "Price" field should be readonly in form "Order Form"

  Scenario: Change product kit item line item price
    When I fill "Order Form" with:
      | ProductKitItem1Price | 12.3456 |
      | ProductKitItem2Price | 23.4567 |
    Then I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $240.37   | $218.52   | $21.85     |
      | Row Total  | $480.79   | $437.08   | $43.71     |
    And I should see next rows in "Backend Order First Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $437.08        | $43.71     |
    And I see next line item discounts for backoffice order:
      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount           |
      | product-kit-01 | $437.086           | $393.376           | $43.70400000000001 |
    And I see next subtotals for "Backend Order":
      | Subtotal | $437.04 |
      | Discount | -$43.70 |
      | Tax      | $43.71  |
      | Total    | $437.05 |
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $0.00     | $0.00     | $0.00      |
      | Total    | $480.79   | $437.08   | $43.71     |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $437.08        | $43.71     |
    And the "Price" field should be readonly in form "Order Form"

#   TODO: Should be uncommented after implementation BB-23120 feature
#  Scenario: Change product kit line item price
#    When I fill "Order Form" with:
#      | Price | 100.00 |
#    Then I should see next rows in "Backend Order First Line Item Taxes Items Table" table
#      |            | Incl. Tax | Excl. Tax | Tax Amount |
#      | Unit Price | $110.00   | $100.00   | $10.00     |
#      | Row Total  | $220.00   | $200.00   | $20.00     |
#    And I should see next rows in "Backend Order First Line Item Taxes Results Table" table
#      | Tax               | Rate | Taxable Amount | Tax Amount |
#      | FLORIDA_SALES_TAX | 10%  | $200.00        | $20.00     |
#    And I see next line item discounts for backoffice order:
#      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount            |
#      | product-kit-01 | $124.571           | $112.221           | $12.468999999999999 |
#    And I see next subtotals for "Backend Order":
#      | Subtotal | $200.00 |
#      | Discount | -$43.70 |
#      | Tax      | $20.00  |
#      | Total    | $220.00 |
#    And I should see next rows in "Backend Order Taxes Totals Table" table
#      |          | Incl. Tax | Excl. Tax | Tax Amount |
#      | Shipping | $0.00     | $0.00     | $0.00      |
#      | Total    | $220.00   | $200.00   | $20.00     |
#    And I should see next rows in "Backend Order Taxes Results Table" table
#      | Tax               | Rate | Taxable Amount | Tax Amount |
#      | FLORIDA_SALES_TAX | 10%  | $200.00        | $20.00     |
#
#  Scenario: Reset product kit line item price
#    When I click "Order Form Line Item 1 Price Overridden"
#    And I click "Reset price"
#    And I click on empty space
#    Then I should see next rows in "Backend Order First Line Item Taxes Items Table" table
#      |            | Incl. Tax | Excl. Tax | Tax Amount |
#      | Unit Price | $240.37   | $218.52   | $21.85     |
#      | Row Total  | $480.74   | $437.04   | $43.70     |
#    And I should see next rows in "Backend Order First Line Item Taxes Results Table" table
#      | Tax               | Rate | Taxable Amount | Tax Amount |
#      | FLORIDA_SALES_TAX | 10%  | $437.04        | $43.70     |
#    And I see next line item discounts for backoffice order:
#      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount            |
#      | product-kit-01 | $124.571           | $112.221           | $12.468999999999999 |
#    And I see next subtotals for "Backend Order":
#      | Subtotal | $437.04 |
#      | Discount | -$43.70 |
#      | Tax      | $43.70  |
#      | Total    | $480.74 |
#    And I should see next rows in "Backend Order Taxes Totals Table" table
#      |          | Incl. Tax | Excl. Tax | Tax Amount |
#      | Shipping | $0.00     | $0.00     | $0.00      |
#      | Total    | $480.74   | $437.04   | $43.70     |
#    And I should see next rows in "Backend Order Taxes Results Table" table
#      | Tax               | Rate | Taxable Amount | Tax Amount |
#      | FLORIDA_SALES_TAX | 10%  | $437.04        | $43.70     |

  Scenario: Add one more product kit line item
    When I click "Add Product"
    And fill "Order Form" with:
      | Product2  | product-kit-01 |
      | Quantity2 | 3              |
    Then I should see next rows in "Backend Order Second Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $137.04   | $124.69   | $12.35     |
      | Row Total  | $411.11   | $374.07   | $37.04     |
    And I should see next rows in "Backend Order Second Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $370.38        | $37.04     |
    And I see next line item discounts for backoffice order:
      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount            |
      | product-kit-01 | $437.086           | $393.376           | $43.70400000000001  |
      | product-kit-01 | $373.703           | $336.663           | $37.406999999999996 |
    And I see next subtotals for "Backend Order":
      | Subtotal | $811.11 |
      | Discount | -$81.11 |
      | Tax      | $80.75  |
      | Total    | $810.75 |
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $0.00     | $0.00     | $0.00      |
      | Total    | $891.90   | $811.15   | $80.75     |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $807.46        | $80.75     |
    And the "Price" field should be readonly in form "Order Form"
    And the "Price2" field should be readonly in form "Order Form"

  Scenario: Product prices already include tax
    When I enable configuration options:
      | oro_tax.product_prices_include_tax |
    And fill "Order Form" with:
      | Product2  | product-kit-01 |
      | Quantity2 | 3              |
    Then I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $218.52   | $198.65   | $19.87     |
      | Row Total  | $437.08   | $397.35   | $39.73     |
    And I should see next rows in "Backend Order First Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $397.35        | $39.73     |
    And I should see next rows in "Backend Order Second Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $124.69   | $113.47   | $11.22     |
      | Row Total  | $374.07   | $340.40   | $33.67     |
    And I should see next rows in "Backend Order Second Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $336.71        | $33.67     |
    And I see next line item discounts for backoffice order:
      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount            |
      | product-kit-01 | $393.376           | $353.646           | $43.70400000000001  |
      | product-kit-01 | $336.663           | $302.993           | $37.406999999999996 |
    And I see next subtotals for "Backend Order":
      | Subtotal | $811.11 |
      | Discount | -$81.11 |
      | Tax      | $73.41  |
      | Total    | $730.00 |
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $0.00     | $0.00     | $0.00      |
      | Total    | $811.15   | $737.74   | $73.41     |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $734.05        | $73.41     |
    And the "Price" field should be readonly in form "Order Form"
    And the "Price2" field should be readonly in form "Order Form"

  Scenario: Calculate taxes after promotions
    When I disable configuration options:
      | oro_tax.product_prices_include_tax |
    And I enable configuration options:
      | oro_tax.calculate_taxes_after_promotions |
    And fill "Order Form" with:
      | Product2  | product-kit-01 |
      | Quantity2 | 3              |
    Then I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $216.33   | $196.67   | $19.67     |
      | Row Total  | $432.65   | $393.32   | $39.33     |
    And I should see next rows in "Backend Order First Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $393.32        | $39.33     |
    And I should see next rows in "Backend Order Second Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $123.33   | $112.22   | $11.11     |
      | Row Total  | $369.99   | $336.66   | $33.33     |
    And I should see next rows in "Backend Order Second Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $333.33        | $33.33     |
    And I see next line item discounts for backoffice order:
      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount            |
      | product-kit-01 | $432.65            | $393.32            | $43.70400000000001  |
      | product-kit-01 | $369.99            | $336.66            | $37.406999999999996 |
    And I see next subtotals for "Backend Order":
      | Subtotal | $811.11 |
      | Discount | -$81.11 |
      | Tax      | $72.67  |
      | Total    | $802.66 |
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $0.00     | $0.00     | $0.00      |
      | Total    | $802.65   | $729.98   | $72.67     |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $726.65        | $72.67     |
    And the "Price" field should be readonly in form "Order Form"
    And the "Price2" field should be readonly in form "Order Form"

  Scenario: Product prices already include tax with enabled option calculate taxes after promotions
    When I enable configuration options:
      | oro_tax.product_prices_include_tax |
    And fill "Order Form" with:
      | Product2  | product-kit-01 |
      | Quantity2 | 3              |
    Then I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $196.67   | $178.79   | $17.88     |
      | Row Total  | $393.32   | $357.56   | $35.76     |
    And I should see next rows in "Backend Order First Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $357.56        | $35.76     |
    And I should see next rows in "Backend Order Second Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $112.22   | $102.12   | $10.10     |
      | Row Total  | $336.66   | $306.36   | $30.30     |
    And I should see next rows in "Backend Order Second Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $303.03        | $30.30     |
    And I see next line item discounts for backoffice order:
      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount            |
      | product-kit-01 | $393.32            | $357.56            | $43.70400000000001  |
      | product-kit-01 | $336.66            | $306.36            | $37.406999999999996 |
    And I see next subtotals for "Backend Order":
      | Subtotal | $811.11 |
      | Discount | -$81.11 |
      | Tax      | $66.06  |
      | Total    | $730.00 |
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $0.00     | $0.00     | $0.00      |
      | Total    | $729.98   | $663.92   | $66.06     |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $660.59        | $66.06     |
    And the "Price" field should be readonly in form "Order Form"
    And the "Price2" field should be readonly in form "Order Form"

  Scenario: Save order and check taxes
    When I disable configuration options:
      | oro_tax.product_prices_include_tax       |
      | oro_tax.calculate_taxes_after_promotions |
    And I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    And I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $240.37   | $218.52   | $21.85     |
      | Row Total  | $480.79   | $437.08   | $43.71     |
    And I should see next rows in "Backend Order First Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $437.08        | $43.71     |
    And I should see next rows in "Backend Order Second Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $137.04   | $124.69   | $12.35     |
      | Row Total  | $411.11   | $374.07   | $37.04     |
    And I should see next rows in "Backend Order Second Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $370.38        | $37.04     |
    And I see next line item discounts for backoffice order:
      | SKU            | Row Total Incl Tax | Row Total Excl Tax | Discount |
      | product-kit-01 | $437.086           | $393.376           | $43.704  |
      | product-kit-01 | $373.703           | $336.663           | $37.407  |
    And I see next subtotals for "Backend Order":
      | Subtotal | $811.11 |
      | Discount | -$81.11 |
      | Tax      | $80.75  |
      | Total    | $810.75 |
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $0.00     | $0.00     | $0.00      |
      | Total    | $891.90   | $811.15   | $80.75     |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $807.46        | $80.75     |
    And the "Price" field should be readonly in form "Order Form"
    And the "Price2" field should be readonly in form "Order Form"
