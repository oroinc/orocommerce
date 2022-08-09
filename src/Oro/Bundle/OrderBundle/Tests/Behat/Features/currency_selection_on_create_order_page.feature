@regression
@ticket-BB-17347
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:PaymentEur.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroCheckoutBundle:ShippingEur.yml
@fixture-OroOrderBundle:products_with_different_currencies.yml

Feature: Currency selection on create order page
  In order to create orders
  As a back office user
  I want to have an ability select currency for the order

  Scenario: Configure tax destination
    Given I login as administrator
    And go to System/Configuration
    And follow "Commerce/Taxation/Tax Calculation" on configuration sidebar
    When uncheck "Use default" for "Use as Base by Default" field
    And fill "Tax Calculation Form" with:
      | Use As Base By Default | Destination |
    And click "Save settings"
    Then I should see "Configuration saved" flash message
#    And follow "Commerce/Catalog/Pricing" on configuration sidebar
#    When I fill "Pricing Form" with:
#      | Enabled Currencies System | false                     |
#      | Enabled Currencies        | [US Dollar ($), Euro (€)] |
#    And click "Save settings"
#    Then I should see "Configuration saved" flash message

  Scenario: Create order selecting currency in back office
    Given I go to Sales/Orders
    And click "Create Order"
    And click "Add Product"
    When I fill "Order Form" with:
      | Customer         | Company A                              |
      | Customer User    | Amanda Cole                            |
      | Billing Address  | ORO, Fifth avenue, ORLANDO FL US 90001 |
      | Shipping Address | ORO, Fifth avenue, ORLANDO FL US 90001 |
      | Product          | SKU                                    |

    Then I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $13.20    | $12.00    | $1.20      |
      | Row Total  | $13.20    | $12.00    | $1.20      |
#    And should see next rows in "Backend Order First Line Item Discounts Items Table" table
#      |           | After Disc. Incl. Tax | After Disc. Excl. Tax | Disc. Amount |
#      | Row Total | $12.20                | $11.00                | $1.00        |
#    When I click "Calculate Shipping"
#    And click "Shipping Method Flat Rate Radio Button"
#    And click "Order Totals"
#    And I see next subtotals for "Backend Order":
#      | Subtotal | Amount |
#      | Subtotal | $12.00 |
#      | Discount | -$1.00 |
#      | Shipping | $3.00  |
#      | Total    | $15.20 |
#
#    When I fill "Order Form" with:
#      | Currency | Euro (€) |
#    Then I should see next rows in "Backend Order First Line Item Taxes Items Table" table
#      |            | Incl. Tax | Excl. Tax | Tax Amount |
#      | Unit Price | €11.00    | €10.00    | €1.00      |
#      | Row Total  | €11.00    | €10.00    | €1.00      |
#    And should see next rows in "Backend Order First Line Item Discounts Items Table" table
#      |           | After Disc. Incl. Tax | After Disc. Excl. Tax | Disc. Amount |
#      | Row Total | €11.00                | €10.00                | €0.00        |
#    When I click "Calculate Shipping"
#    And click "Shipping Method Flat Rate Radio Button"
#    And click "Order Totals"
#    And I see next subtotals for "Backend Order":
#      | Subtotal | €10.00 |
#      | Shipping | €2.80  |
#      | Total    | €13.80 |
#
#    When I click "Save and Close"
#    Then I should see "Order has been saved" flash message
#    And I should see "Currency EUR"
