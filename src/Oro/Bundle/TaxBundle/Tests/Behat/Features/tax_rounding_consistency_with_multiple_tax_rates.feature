@ticket-BB-27383
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroTaxBundle:tax_rounding_consistency_with_multiple_tax_rates.yml

Feature: Tax rounding consistency with multiple tax rates
  As a buyer
  I want the tax amount shown in the checkout Tax line to match the tax amount added to the order grand total
  when "Start Calculation On" is set to "Total" and products have different tax rates

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I enable configuration options:
      | oro_tax.tax_enable |
    And I set configuration property "oro_tax.use_as_base_by_default" to "destination"
    And I set configuration property "oro_tax.start_calculation_with" to "row_total"
    And I set configuration property "oro_tax.start_calculation_on" to "total"

  Scenario: Tax line and order grand total are consistent on checkout when multiple tax rates applied
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And I check "Ship to this address" on the checkout page
    And I click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    Then I should see Checkout Totals with data:
      | Subtotal | $5.00 |
      | Shipping | $3.00 |
      | Tax      | $0.68 |
    And should see "Total $8.68"
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title

  Scenario: Tax line and order grand total are consistent in back-office order view
    Given I proceed as the Admin
    And login as administrator
    And I go to Sales/ Orders
    When I filter "Total ($)" as equals "$8.68"
    Then number of records should be 1
    When I click "View" on first row in grid
    And I click "Totals"
    Then I see next subtotals for "Backend Order":
      | Subtotal | Amount |
      | Subtotal | $5.00  |
      | Shipping | $3.00  |
      | Tax      | $0.68  |
      | Total    | $8.68  |
