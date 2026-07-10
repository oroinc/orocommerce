@ticket-BB-27383
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroTaxBundle:order_discount_tax_precision_with_row_total.yml

Feature: Order discount tax precision with row total
  In order to pay the correct tax amount on discounted orders
  As a buyer
  I want the tax to be calculated on the correct taxable base after proportional discount distribution

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I enable configuration options:
      | oro_tax.tax_enable |
    And I change configuration options:
      | oro_tax.use_as_base_by_default           | destination |
      | oro_tax.start_calculation_with           | row_total   |
      | oro_tax.start_calculation_on             | total       |
      | oro_tax.calculate_taxes_after_promotions | true        |

  Scenario: Tax is calculated on correct taxable base after proportional $100 discount distribution
    # Products: $10.95 + ($2.88 x 5 = $14.40) + $350.00 = $375.35
    # $100 discount distributed proportionally:
    #   item1: round(10.95 * 100 / 375.35, 2) = $2.92  → taxable $8.03
    #   item2: round(14.40 * 100 / 375.35, 2) = $3.84  → taxable $10.56
    #   item3 (remainder):  100 - 2.92 - 3.84  = $93.24 → taxable $256.76
    # Total taxable: $275.35, VAT 22%: $60.58
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    When I open page with shopping list List 1
    And I click "Create Order"
    And I click "Ship to This Address"
    And I click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I click "Expand Checkout Footer"
    Then I should see Checkout Totals with data:
      | Subtotal | $375.35  |
      | Discount | -$100.00 |
      | Shipping | $3.00    |
      | Tax      | $60.58   |
    And should see "Total: $338.93"

  Scenario: Tax is calculated on correct taxable base with Start Calculation With Unit Price
    # Same data as above with "Start Calculation With: Unit Price". item2's $10.56 doesn't divide
    # evenly by 5 ($2.112/unit); rounding to $2.11 before multiplying back undercounts by $0.01.
    # Taxable base and tax must still match the Row Total scenario: $275.35 / $60.58.
    Given I proceed as the Admin
    And I change configuration options:
      | oro_tax.start_calculation_with | unit_price |
    And I proceed as the Buyer
    When I open page with shopping list List 2
    And I click "Create Order"
    And I click "Ship to This Address"
    And I click "Continue"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    And I click "Expand Checkout Footer"
    Then I should see Checkout Totals with data:
      | Subtotal | $375.35  |
      | Discount | -$100.00 |
      | Shipping | $3.00    |
      | Tax      | $60.58   |
    And should see "Total: $338.93"
