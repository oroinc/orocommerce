@skip
@feature-BB-21128
@fixture-OroTaxBundle:product-kit/create_order_with_product_kits_and_taxes.yml

Feature: Create Order with Product Kits and Taxes

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
      | Unit Price | $137.16   | $124.69   | $12.47     |
      | Row Total  | $137.16   | $124.69   | $12.47     |
    And I should see next rows in "Backend Order First Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $124.69        | $12.47     |
    And I see next subtotals for "Backend Order":
      | Subtotal | $124.69 |
      | Tax      | $12.47  |
      | Total    | $137.16 |
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $0.00     | $0.00     | $0.00      |
      | Total    | $137.16   | $124.69   | $12.47     |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $124.69        | $12.47     |

  Scenario: Change product kit line item quantity
    When I fill "Order Form" with:
      | Quantity | 2 |
    Then I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $137.16   | $124.69   | $12.47     |
      | Row Total  | $274.32   | $249.38   | $24.94     |
    And I should see next rows in "Backend Order First Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $249.38        | $24.94     |
    And I see next subtotals for "Backend Order":
      | Subtotal | $249.38 |
      | Tax      | $24.94  |
      | Total    | $274.32 |
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $0.00     | $0.00     | $0.00      |
      | Total    | $274.32   | $249.38   | $24.94     |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $249.38        | $24.94     |

  Scenario: Add product kit item line item product
    When I fill "Order Form" with:
      | ProductKitItem1Product | simple-product-03 - Simple Product 03 |
    Then I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $141.23   | $128.39   | $12.84     |
      | Row Total  | $282.46   | $256.78   | $25.68     |
    And I should see next rows in "Backend Order First Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $256.78        | $25.68     |
    And I see next subtotals for "Backend Order":
      | Subtotal | $256.78 |
      | Tax      | $25.68  |
      | Total    | $282.46 |
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $0.00     | $0.00     | $0.00      |
      | Total    | $282.46   | $256.78   | $25.68     |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $256.78        | $25.68     |

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
    And I see next subtotals for "Backend Order":
      | Subtotal | $259.26 |
      | Tax      | $25.93  |
      | Total    | $285.19 |
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $0.00     | $0.00     | $0.00      |
      | Total    | $285.19   | $259.26   | $25.93     |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $259.26        | $25.93     |

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
    And I see next subtotals for "Backend Order":
      | Subtotal | $276.54 |
      | Tax      | $27.65  |
      | Total    | $304.19 |
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $0.00     | $0.00     | $0.00      |
      | Total    | $304.19   | $276.54   | $27.65     |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $276.54        | $27.65     |

  Scenario: Change product kit item line item price
    When I fill "Order Form" with:
      | ProductKitItem1Price | 12.3456 |
      | ProductKitItem2Price | 23.4567 |
    Then I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $240.37   | $218.52   | $21.85     |
      | Row Total  | $480.74   | $437.04   | $43.70     |
    And I should see next rows in "Backend Order First Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $437.04        | $43.70     |
    And I see next subtotals for "Backend Order":
      | Subtotal | $437.04 |
      | Tax      | $43.70  |
      | Total    | $480.74 |
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $0.00     | $0.00     | $0.00      |
      | Total    | $480.74   | $437.04   | $43.70     |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $437.04        | $43.70     |

  Scenario: Change product kit line item price
    When I fill "Order Form" with:
      | Price | 100.00 |
    Then I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $110.00   | $100.00   | $10.00     |
      | Row Total  | $220.00   | $200.00   | $20.00     |
    And I should see next rows in "Backend Order First Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $200.00        | $20.00     |
    And I see next subtotals for "Backend Order":
      | Subtotal | $200.00 |
      | Tax      | $20.00  |
      | Total    | $220.00 |
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $0.00     | $0.00     | $0.00      |
      | Total    | $220.00   | $200.00   | $20.00     |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $200.00        | $20.00     |

  Scenario: Reset product kit line item price
    When I click "Order Form Line Item 1 Price Overridden"
    And I click "Reset price"
    And I click on empty space
    Then I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $240.37   | $218.52   | $21.85     |
      | Row Total  | $480.74   | $437.04   | $43.70     |
    And I should see next rows in "Backend Order First Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $437.04        | $43.70     |
    And I see next subtotals for "Backend Order":
      | Subtotal | $437.04 |
      | Tax      | $43.70  |
      | Total    | $480.74 |
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $0.00     | $0.00     | $0.00      |
      | Total    | $480.74   | $437.04   | $43.70     |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $437.04        | $43.70     |

  Scenario: Add one more product kit line item
    When I click "Add Product"
    And fill "Order Form" with:
      | Product2  | product-kit-01 |
      | Quantity2 | 3              |
    Then I should see next rows in "Backend Order Second Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $137.16   | $124.69   | $12.47     |
      | Row Total  | $411.48   | $374.07   | $37.41     |
    And I should see next rows in "Backend Order Second Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $374.07        | $37.41     |
    And I see next subtotals for "Backend Order":
      | Subtotal | $811.11 |
      | Tax      | $81.11  |
      | Total    | $892.22 |
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $0.00     | $0.00     | $0.00      |
      | Total    | $892.22   | $811.11   | $81.11     |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $811.11        | $81.11     |

  Scenario: Save order and check taxes
    When I save form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    And I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $240.37   | $218.52   | $21.85     |
      | Row Total  | $480.74   | $437.04   | $43.70     |
    And I should see next rows in "Backend Order First Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $437.04        | $43.70     |
    And I should see next rows in "Backend Order Second Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $137.16   | $124.69   | $12.47     |
      | Row Total  | $411.48   | $374.07   | $37.41     |
    And I should see next rows in "Backend Order Second Line Item Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $374.07        | $37.41     |
    And I see next subtotals for "Backend Order":
      | Subtotal | $811.11 |
      | Tax      | $81.11  |
      | Total    | $892.22 |
    And I should see next rows in "Backend Order Taxes Totals Table" table
      |          | Incl. Tax | Excl. Tax | Tax Amount |
      | Shipping | $0.00     | $0.00     | $0.00      |
      | Total    | $892.22   | $811.11   | $81.11     |
    And I should see next rows in "Backend Order Taxes Results Table" table
      | Tax               | Rate | Taxable Amount | Tax Amount |
      | FLORIDA_SALES_TAX | 10%  | $811.11        | $81.11     |
