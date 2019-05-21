@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroTaxBundle:OrderTaxCurrencies.yml
@ticket-BB-16690

Feature: Tax for overridden shipping cost
  In order to be able to override order shipping cost
  As an administrator
  I want the shipping tax to be calculated on the overridden shipping cost

  Scenario: Configure taxes
    Given I login as administrator
    And I go to System/Configuration
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
    And I follow "Commerce/Taxation/Shipping" on configuration sidebar
    And uncheck "Use default" for "Tax Code" field
    And uncheck "Use default" for "Shipping Rates Include Tax" field
    And I fill "Tax Shipping Form" with:
      | Tax Code                   | taxable |
      | Shipping Rates Include Tax | true    |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Create order with overridden shipping cost
    And I go to Sales/Orders
    When I click "Create Order"
    And click "Add Product"
    And fill "Order Form" with:
      | Customer User | Amanda Cole |
      | Product       | SKU123      |
    And I type "5" in "First Product Quantity Field In Order"
    And I click "Calculate Shipping"
    And I click "Shipping Method Flat Rate Radio Button"
    Then I should see "Subtotal $10.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $1.15"
    And I should see "Total $14.15"
    When I type "0" in "Overridden Shipping Cost Amount"
    And I click on empty space
    Then I should see "Subtotal $10.00"
    And I should see "Shipping $0.00"
    And I should see "Tax $0.90"
    And I should see "Total $10.90"
    When I save and close form
    Then I should see "Order has been saved" flash message
    And I should see "Subtotal $10.00"
    And I should see "Shipping $0.00"
    And I should see "Tax $0.90"
    And I should see "Total $10.90"
    When I go to Sales/Orders
    And I should see "1" in grid with following data:
      | Order Number | 1      |
      | Total        | $10.90 |
