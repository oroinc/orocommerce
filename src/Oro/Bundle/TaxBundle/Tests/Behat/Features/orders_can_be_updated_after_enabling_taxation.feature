@ticket-BB-21759

@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroTaxBundle:OrderTaxCurrencies.yml

Feature: Orders can be updated after enabling taxation
  Check if it is possible to update the order after re-enabling taxation

  Scenario: Disable taxes
    Given I login as administrator
    And go to System/Configuration
    And follow "Commerce/Taxation/Tax Calculation" on configuration sidebar
    When uncheck "Use default" for "Enabled" field
    And fill "Tax Calculation Form" with:
      | Enabled | false |
    And save form
    Then I should see "Configuration saved" flash message

  Scenario: Create order
    Given I go to Sales/Orders
    When I click "Create Order"
    And click "Add Product"
    And fill "Order Form" with:
      | Customer User | Amanda Cole |
      | Product       | SKU123      |
      | Quantity      | 5           |
    And click "Calculate Shipping"
    And click "Shipping Method Flat Rate Radio Button"
    When I save and close form
    Then I should see "Order has been saved" flash message

  Scenario: Enable taxes
    Given I go to System/Configuration
    And follow "Commerce/Taxation/Tax Calculation" on configuration sidebar
    When check "Use default" for "Enabled" field
    And I save form
    Then I should see "Configuration saved" flash message

  Scenario: Update order
    Given I go to Sales/Orders
    When I click edit "1" in grid
    And fill "Order Form" with:
      | Quantity | 6 |
    And save and close form
    And click "Save" in modal window
    Then I should see "Order has been saved" flash message
    And should not see "There was an error performing the requested operation. Please try again or contact us for assistance."
