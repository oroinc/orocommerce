@ticket-BB-17312
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroTaxBundle:digital_products_taxes.yml
Feature: Order taxes are recalculated correctly after tax rate is changed
  As a Administrator
  I want to have a possibility to change tax rate without affecting an existing orders tax rate

  Scenario: Prepare sessions
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I login as administrator and use in "second_session" as "Admin"

  Scenario: Configure Shipping Origin and Digital Products Tax Codes
    Given I go to System/Configuration
    And I follow "Commerce/Taxation/Tax Calculation" on configuration sidebar
    And uncheck "Use default" for "Use as Base by Default" field
    And uncheck "Use default" for "Origin Address" field
    And I fill "Tax Calculation Form" with:
      | Use As Base By Default | Shipping Origin |
      | Origin Country         | Germany         |
      | Origin Region          | Berlin          |
      | Origin Zip Code        | 10115           |
    And I save form
    Then I should see "Configuration saved" flash message
    And I go to System/Configuration
    And I follow "Commerce/Taxation/US Sales Tax" on configuration sidebar
    And uncheck "Use default" for "Digital Products Tax Codes" field
    And I fill "Tax US Sales Tax Form" with:
      | Digital Products Tax Codes | digital |
    And I save form
    Then I should see "Configuration saved" flash message
    And I go to System/Configuration
    And I follow "Commerce/Taxation/EU VAT Tax" on configuration sidebar
    And uncheck "Use default" for "Digital Products Tax Codes" field
    And I fill "Tax EU Vat Tax Form" with:
      | Digital Products Tax Codes | digital |
    And I save form
    Then I should see "Configuration saved" flash message
    And There are products in the system available for order

  Scenario: Customer buys digital and non-digital products
    Given I operate as the Buyer
    When I open page with shopping list List 1
    And I click "Create Order"
    And I select "ORO, Second avenue, 1010 Vienna, Austria" on the "Billing Information" checkout step and press Continue
    And I select "ORO, Second avenue, 1010 Vienna, Austria" on the "Shipping Information" checkout step and press Continue
    And I click "Continue"
    And I click "Continue"
    And I should see "Subtotal $20.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $3.00"
    And I should see "Total $26.00"
    And I fill form with:
      | PO Number | foo_po_number |
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Administrator change tax rate and check the order taxes are not recalculated
    Given I operate as the Admin
    And I go to Taxes/Taxes
    And I click "Edit" on row "digital_items" in grid
    And I fill form with:
      | Rate | 50        |
    And I save form
    And I go to Sales/Orders
    And I click "Edit" on row "foo_po_number" in grid
    Then I should see next rows in "Backend Order Second Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $2.40     | $2.00     | $0.40      |
      | Row Total  | $12.00    | $10.00    | $2.00      |
    And I should see next rows in "Backend Order Second Line Item Taxes Results Table" table
      | Tax           |Rate  | Taxable Amount | Tax Amount |
      | digital_items | 20%  | $10.00         | $2.00      |
    And I save form
    And I should see "Tax $3.00"
    And I should see "Total $26.00"

  Scenario: Customer user check order taxes is not changed after tax rate changed
    Given I operate as the Buyer
    When I follow "Account"
    And I click "Order History"
    And click "view" on first row in grid
    Then I should see "Subtotal $20.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $3.00"
    And I should see "Total $26.00"

  Scenario: Administrator change order field which could not affect taxes calculation and taxes are not recalculated
    Given I operate as the Admin
    And fill "Order Form" with:
      | Customer Notes | Foo notes |
    And I save form
    Then I should see next rows in "Backend Order Second Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $2.40     | $2.00     | $0.40      |
      | Row Total  | $12.00    | $10.00    | $2.00      |
    And I should see next rows in "Backend Order Second Line Item Taxes Results Table" table
      | Tax           |Rate  | Taxable Amount | Tax Amount |
      | digital_items | 20%  | $10.00         | $2.00      |

  Scenario: Customer user check order taxes is not changed after order changed
    Given I operate as the Buyer
    And I reload the page
    Then I should see "Subtotal $20.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $3.00"
    And I should see "Total $26.00"
    And I should see "Foo notes"

  Scenario: Administrator change the order item quantity and check taxes are recalculated
    Given I operate as the Admin
    And fill "Order Form" with:
      | Quantity2 | 1     |
    Then I should see next rows in "Backend Order Second Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $3.00     | $2.00     | $1.00      |
      | Row Total  | $3.00     | $2.00     | $1.00      |
    And I should see next rows in "Backend Order Second Line Item Taxes Results Table" table
      | Tax           |Rate  | Taxable Amount | Tax Amount |
      | digital_items | 50%  | $2.00          | $1.00      |
    And fill "Order Form" with:
      | Quantity2 | 5     |
    Then I should see next rows in "Backend Order Second Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $2.40     | $2.00     | $0.40      |
      | Row Total  | $12.00    | $10.00    | $2.00      |
    And I should see next rows in "Backend Order Second Line Item Taxes Results Table" table
      | Tax           |Rate  | Taxable Amount | Tax Amount |
      | digital_items | 20%  | $10.00         | $2.00      |

  Scenario: Administrator remove order item price and check taxes are recalculated
    Given I operate as the Admin
    And fill "Order Form" with:
      | Product | SKU124     |
    Then I should see next rows in "Backend Order Second Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount  |
      | Unit Price | $3.00     | $2.00     | $1.00       |
      | Row Total  | $15.00    | $10.00    | $5.00       |
    And I should see next rows in "Backend Order Second Line Item Taxes Results Table" table
      | Tax           |Rate  | Taxable Amount | Tax Amount |
      | digital_items | 50%  | $10.00         | $5.00      |
    And fill "Order Form" with:
      | Product  | SKU123     |
      | Quantity | 5          |
      | Price    | 2          |
    Then I should see next rows in "Backend Order Second Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $2.40     | $2.00     | $0.40      |
      | Row Total  | $12.00    | $10.00    | $2.00      |
    And I should see next rows in "Backend Order Second Line Item Taxes Results Table" table
      | Tax           |Rate  | Taxable Amount | Tax Amount |
      | digital_items | 20%  | $10.00         | $2.00      |

  Scenario: Administrator change the order item price and check taxes are recalculated
    Given I operate as the Admin
    And fill "Order Form" with:
      | Price2 | 10     |
    And I save form
    And I should see "Review Shipping Cost"
    And I click "Save" in modal window
    Then I should see next rows in "Backend Order Second Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount  |
      | Unit Price | $15.00    | $10.00    | $5.00       |
      | Row Total  | $75.00    | $50.00    | $25.00      |
    And I should see next rows in "Backend Order Second Line Item Taxes Results Table" table
      | Tax           |Rate  | Taxable Amount | Tax Amount |
      | digital_items | 50%  | $50.00         | $25.00     |

  Scenario: Customer user check order taxes is changed after order item price was changed by Administrator
    Given I operate as the Buyer
    And I reload the page
    Then I should see "Subtotal $60.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $26.00"
    And I should see "Total $89.00"
