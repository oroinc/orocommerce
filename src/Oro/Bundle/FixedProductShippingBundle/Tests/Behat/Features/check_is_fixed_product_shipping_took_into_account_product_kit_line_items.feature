@ticket-BB-23343
@fixture-OroFixedProductShippingBundle:FixedProductShippingIntegrations.yml
@fixture-OroFixedProductShippingBundle:FixedProductShipping.yml
@fixture-OroFixedProductShippingBundle:FixedProductShippingCheckout.yml
@fixture-OroFixedProductShippingBundle:PaymentIntegration.yml
@fixture-OroFixedProductShippingBundle:Payment.yml

Feature: Check is fixed product shipping took into account product kit line items

  Scenario: Check order fixed shipping const for product kit without shipping price and "Kit product and kit items" default shipping calculation mode on frontstore
    Given I login as AmandaRCole@example.org buyer
    When I open page with shopping list List 1
    And I click Configure "productkit1" in grid
    And I click "Update List 1"
    And I click "Create Order"
    And I click "Ship to This Address"
    And I click "Continue"
    Then I should see "Fixed Product 1 (Surcharge Type: Percent, Surcharge On: Product Price, Surcharge Amount: 10%): $9.10"
    And I should see "Fixed Product 2 (Surcharge Type: Percent, Surcharge On: Product Shipping Cost, Surcharge Amount: 15%): $4.60"
    And I should see "Fixed Product 3 (Surcharge Type: Fixed Amount, Surcharge Amount: $20.00): $24.00"

  Scenario: Check order fixed shipping const for product kit without shipping price and "Only kit items" shipping calculation mode on frontstore
    When I open page with shopping list List 2
    And I click Configure "productkit2" in grid
    And I click "Update List 2"
    And I click "Create Order"
    And I click "Ship to This Address"
    And I click "Continue"
    Then I should see "Fixed Product 1 (Surcharge Type: Percent, Surcharge On: Product Price, Surcharge Amount: 10%): $16.00"
    And I should see "Fixed Product 2 (Surcharge Type: Percent, Surcharge On: Product Shipping Cost, Surcharge Amount: 15%): $9.20"
    And I should see "Fixed Product 3 (Surcharge Type: Fixed Amount, Surcharge Amount: $20.00): $28.00"

  Scenario: Check order fixed shipping const for product kit without shipping price and "Only kit product itself" shipping calculation mode on frontstore
    When I open page with shopping list List 3
    And I click Configure "productkit3" in grid
    And I click "Update List 3"
    And I click "Create Order"
    And I click "Ship to This Address"
    And I click "Continue"
    Then I should see "Fixed Product 1 (Surcharge Type: Percent, Surcharge On: Product Price, Surcharge Amount: 10%): $3.90"
    And I should see "Fixed Product 2 (Surcharge Type: Percent, Surcharge On: Product Shipping Cost, Surcharge Amount: 15%): $0.00"
    And I should see "Fixed Product 3 (Surcharge Type: Fixed Amount, Surcharge Amount: $20.00): $20.00"

  Scenario: Check order fixed shipping const for product kit with shipping price and "Kit product and kit items" shipping calculation mode on frontstore
    When I open page with shopping list List 4
    And I click Configure "productkit4" in grid
    And I click "Update List 4"
    And I click "Create Order"
    And I click "Ship to This Address"
    And I click "Continue"
    Then I should see "Fixed Product 1 (Surcharge Type: Percent, Surcharge On: Product Price, Surcharge Amount: 10%): $53.60"
    And I should see "Fixed Product 2 (Surcharge Type: Percent, Surcharge On: Product Shipping Cost, Surcharge Amount: 15%): $36.80"
    And I should see "Fixed Product 3 (Surcharge Type: Fixed Amount, Surcharge Amount: $20.00): $52.00"

  Scenario: Check order fixed shipping const for product kit with shipping price and "Only kit items" shipping calculation mode on frontstore
    When I open page with shopping list List 5
    And I click Configure "productkit5" in grid
    And I click "Update List 5"
    And I click "Create Order"
    And I click "Ship to This Address"
    And I click "Continue"
    Then I should see "Fixed Product 1 (Surcharge Type: Percent, Surcharge On: Product Price, Surcharge Amount: 10%): $40.00"
    And I should see "Fixed Product 2 (Surcharge Type: Percent, Surcharge On: Product Shipping Cost, Surcharge Amount: 15%): $23.00"
    And I should see "Fixed Product 3 (Surcharge Type: Fixed Amount, Surcharge Amount: $20.00): $40.00"

  Scenario: Check order fixed shipping const for product kit with shipping price and "Only kit product itself" shipping calculation mode on frontstore
    When I open page with shopping list List 6
    And I click Configure "productkit6" in grid
    And I click "Update List 6"
    And I click "Create Order"
    And I click "Ship to This Address"
    And I click "Continue"
    Then I should see "Fixed Product 1 (Surcharge Type: Percent, Surcharge On: Product Price, Surcharge Amount: 10%): $45.60"
    And I should see "Fixed Product 2 (Surcharge Type: Percent, Surcharge On: Product Shipping Cost, Surcharge Amount: 15%): $41.40"
    And I should see "Fixed Product 3 (Surcharge Type: Fixed Amount, Surcharge Amount: $20.00): $56.00"
