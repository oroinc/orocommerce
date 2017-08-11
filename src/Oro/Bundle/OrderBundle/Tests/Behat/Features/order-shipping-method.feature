@fixture-OroDPDBundle:DPDIntegration.yml
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroOrderBundle:OrderShippingMethod.yml
Feature: Select shipping method for Order on backoffice
  In order to check shipping rules edit
  As a Administrator
  I want to create and edit order from the admin panel

  Scenario: Create order with DPD shipping rule
    Given I login as administrator
    And go to Sales/ Orders
    And click "Create Order"
    And click "Add Product"
    And fill "Order Form" with:
      | Customer         | Company A                                                           |
      | Customer User    | Amanda Cole                                                         |
      | Billing Address  | Amanda Cole, ORO, VOTUM GmbH Ohlauer Str. 43, 10999 Berlin, Germany |
      | Shipping Address | Amanda Cole, ORO, VOTUM GmbH Ohlauer Str. 43, 10999 Berlin, Germany |
      | Product          | SKU1                                                                |
      | Quantity         | 5                                                                   |
    And click "Shipping Information"
    When click "Calculate Shipping Button"
    Then I should see "Flat Rate $50.00"
    And I should see "DPD DPD Classic $20.00"
    When click "Order Flat Rate"
    Then should see "Shipping $50.00"
    When click "Order DPD Classic"
    Then should see "Shipping $20.00"
    And save and close form
    And should see "Order has been saved" flash message

  Scenario: Edit shipping method of a order
    Given click "Edit"
    Then I should not see an "Calculate Shipping Button" element
    And I should not see "Previously Selected Shipping Method DPD, DPD Classic: $20.00"
    And should see "Shipping $20.00"
    And click "Order Flat Rate"
    Then should see "Shipping $50.00"
    And I should not see "Previously Selected Shipping Method Flat Rate: $50.00"
    And should see "Previously Selected Shipping Method DPD, DPD Classic: $20.00"
    And save and close form
    And should see "Order has been saved" flash message

  Scenario: Disabled shipping method
    Given I go to System/ Shipping Rules
    And click disable "Flat Rate" in grid
    When go to Sales/ Orders
    And click edit "Amanda Cole" in grid
    Then I should see "Previously Selected Shipping Method Flat Rate: $50.00"
    And should see "Shipping $50.00"

  Scenario: Overridden shipping cost
    Given I type "100" in "Overridden Shipping Cost Amount"
    # Field should loss focus for reloading the subtotals, should be removed after BB-11017 resolving
    And I click "Shipping Information"
    Then should see "Shipping $100.00"
    And I should see "Previously Selected Shipping Method Flat Rate: $50.00"
    When click "Order DPD Classic"
    Then should see "Shipping $100.00"
    And I should not see "Previously Selected Shipping Method DPD, DPD Classic: $20.00"
    And should see "Previously Selected Shipping Method Flat Rate: $50.00"
    When save and close form
    Then I should see "Shipping Method DPD, DPD Classic"
    And I should see "Shipping Cost $100.00"
    And I should see "Shipping $100.00"
