@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroTaxBundle:OrderTaxCurrencies.yml
@ticket-BB-16690
@ticket-BB-21025
@ticket-BB-21329
@ticket-BB-16052

Feature: Verification Calculation of Taxes Toggling Shipping Rates or Shipping Rates Include Tax
  In order to verify calculation of taxes is correct
  As an administrator
  I want to toggle shipping rates or shipping Rates include tax on or off and see the total price is correct.

  Scenario: Configure taxes
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
      | Use As Base By Default | Origin  |
      | Origin Country         | Germany |
      | Origin Region          | Berlin  |
      | Origin Zip Code        | 10115   |
    And I save form
    Then I should see "Configuration saved" flash message
    When I follow "Commerce/Taxation/Shipping" on configuration sidebar
    And uncheck "Use default" for "Tax Code" field
    And I fill "Tax Shipping Form" with:
      | Tax Code                   | taxable |
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Create order to verify calculation of taxes
    And I go to Sales/Orders
    When I click "Create Order"
    And click "Add Product"
    And fill "Order Form" with:
      | Customer User | Amanda Cole |
      | Product       | SKU123      |
      | Quantity      | 5           |
    And I click "Calculate Shipping"
    And I click "Shipping Method Flat Rate Radio Button"
    Then I should see "Subtotal $10.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $1.17"
    And I should see "Total $14.17"
    When I save and close form
    Then I should see "Order has been saved" flash message
    And I should see "Subtotal $10.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $1.17"
    And I should see "Total $14.17"
    When I go to Sales/Orders
    And I should see "1" in grid with following data:
      | Order Number | 1      |
      | Total        | $14.17 |

  Scenario: Create order with shipping rates include tax on
    Given I go to System/Configuration
    When I follow "Commerce/Taxation/Shipping" on configuration sidebar
    And uncheck "Use default" for "Shipping Rates Include Tax" field
    And I check "Shipping Rates Include Tax"
    And I save form
    Then I should see "Configuration saved" flash message
    And I go to Sales/Orders
    When I click "Create Order"
    And click "Add Product"
    And fill "Order Form" with:
      | Customer User | Amanda Cole |
      | Product       | SKU123      |
      | Quantity      | 5           |
    And I click "Calculate Shipping"
    And I click "Shipping Method Flat Rate Radio Button"
    Then I should see "Subtotal $10.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $1.15"
    And I should see "Total $13.90"
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
    Then there are 2 records in grid
    And I should see following grid containing rows:
      | Order Number | Total  |
      | 1            | $14.17 |
      | 2            | $10.90 |

  Scenario: Create order without shipping method and edit order again
    Given I go to Sales/Orders
    When I click "Create Order"
    And click "Add Product"
    And fill "Order Form" with:
      | Customer User | Amanda Cole |
      | Product       | SKU123      |
      | Quantity      | 5           |
    When I save and close form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    When I click "Edit"
    And I click "Shipping Information"
    And I click "Shipping Method Flat Rate Radio Button"
    Then I should see "Subtotal $10.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $1.15"
    And I should see "Total $13.90"
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
    Then there are 3 records in grid
    And I should see following grid containing rows:
      | Order Number | Total  |
      | 1            | $14.17 |
      | 2            | $10.90 |
      | 3            | $10.90 |

  Scenario: Create Order With Product Prices Include Tax On
    Given I go to System/Configuration
    When I follow "Commerce/Taxation/Shipping" on configuration sidebar
    And check "Use default" for "Shipping Rates Include Tax" field
    And I save form
    Then I should see "Configuration saved" flash message
    When I follow "Commerce/Taxation/Tax Calculation" on configuration sidebar
    And uncheck "Use default" for "Product Prices Include Tax" field
    And I check "Product Prices Include Tax"
    And I save form
    Then I should see "Configuration saved" flash message
    And I go to Sales/Orders
    When I click "Create Order"
    And click "Add Product"
    And fill "Order Form" with:
      | Customer User | Amanda Cole |
      | Product       | SKU123      |
      | Quantity      | 5           |
    And I click "Calculate Shipping"
    And I click "Shipping Method Flat Rate Radio Button"
    Then I should see "Subtotal $10.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $1.10"
    And I should see "Total $13.27"
    When I save and close form
    Then I should see "Order has been saved" flash message
    And I should see "Subtotal $10.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $1.10"
    And I should see "Total $13.27"
    When I go to Sales/Orders
    Then there are 4 records in grid
    And I should see following grid containing rows:
      | Order Number | Total  |
      | 1            | $14.17 |
      | 2            | $10.90 |
      | 3            | $10.90 |
      | 4            | $13.27 |

  Scenario: Create Order With Both Shipping Rates and Product Prices Include Tax On
    Given I go to System/Configuration
    When I follow "Commerce/Taxation/Shipping" on configuration sidebar
    And uncheck "Use default" for "Shipping Rates Include Tax" field
    And I check "Shipping Rates Include Tax"
    And I save form
    Then I should see "Configuration saved" flash message
    And I go to Sales/Orders
    When I click "Create Order"
    And click "Add Product"
    And fill "Order Form" with:
      | Customer User | Amanda Cole |
      | Product       | SKU123      |
      | Quantity      | 5           |
    And I click "Calculate Shipping"
    And I click "Shipping Method Flat Rate Radio Button"
    Then I should see "Subtotal $10.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $1.07"
    And I should see "Total $13.00"
    When I save and close form
    Then I should see "Order has been saved" flash message
    And I should see "Subtotal $10.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $1.07"
    And I should see "Total $13.00"
    When I go to Sales/Orders
    Then there are 5 records in grid
    And I should see following grid containing rows:
      | Order Number | Total  |
      | 1            | $14.17 |
      | 2            | $10.90 |
      | 3            | $10.90 |
      | 4            | $13.27 |
      | 5            | $13.00 |

  Scenario: Turn on tax calculation after promotion and create order
    Given I go to System/Configuration
    When I follow "Commerce/Taxation/Tax Calculation" on configuration sidebar
    And uncheck "Use default" for "Calculate Taxes After Promotions" field
    And I check "Calculate Taxes After Promotions"
    And I save form
    Then I should see "Configuration saved" flash message
    When I go to Sales/Orders
    When I click "Create Order"
    And click "Add Product"
    And fill "Order Form" with:
      | Customer User | Amanda Cole |
      | Product       | SKU123      |
      | Quantity      | 5           |
    When I save and close form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
    When I click "Edit"
    And I click "Shipping Information"
    And I click "Shipping Method Flat Rate Radio Button"
    Then I should see "Subtotal $10.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $1.07"
    And I should see "Total $13.00"
    When I save and close form
    Then I should see "Order has been saved" flash message
    And I should see "Subtotal $10.00"
    And I should see "Shipping $3.00"
    And I should see "Tax $1.07"
    And I should see "Total $13.00"
    When I go to Sales/Orders
    Then there are 6 records in grid
    And I should see following grid containing rows:
      | Order Number | Total  |
      | 1            | $14.17 |
      | 2            | $10.90 |
      | 3            | $10.90 |
      | 4            | $13.27 |
      | 5            | $13.00 |
      | 6            | $13.00 |
