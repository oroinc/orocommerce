@ticket-BB-15521
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroTaxBundle:OrderTaxCurrencies.yml

Feature: Order tax currencies
  In order to manage orders
  As an Administrator
  I want to see correct currencies in tax tables for line items

  Scenario: Feature Background
    Given I login as administrator
    And I go to System / Configuration
    And I follow "System Configuration/General Setup/Currency" on configuration sidebar
    And I click "EuroAsDefaultValue"
    And I click "Yes"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message
    When I follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "Pricing Form" with:
      | Enabled Currencies System | false                     |
      | Enabled Currencies        | [US Dollar ($), Euro (€)] |
    And click "Save settings"
    Then I should see "Configuration saved" flash message
    When I follow "Commerce/Taxation/Tax Calculation" on configuration sidebar
    And uncheck "Use default" for "Origin Address" field
    And I fill "Tax Calculation Form" with:
      | Use As Base By Default | Shipping Origin |
      | Origin Country         | Germany         |
      | Origin Region          | Berlin          |
      | Origin Zip Code        | 10115           |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message
    When I go to Sales / Orders
    And I click "Create Order"
    And I click on "Backend Order Add Product Button"
    Then I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | €0.00     | €0.00     | €0.00      |
      | Row Total  | €0.00     | €0.00     | €0.00      |
    When I fill "Order Form" with:
      | Customer | Company A |
      | Product  | SKU1      |
    Then I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | €43.60    | €40.00    | €3.60      |
      | Row Total  | €43.60    | €40.00    | €3.60      |
    And I should see next rows in "Backend Order First Line Item Taxes Results Table" table
      | Tax          |Rate | Taxable Amount | Tax Amount |
      | berlin_sales | 9%  | €40.00         | €3.60      |
