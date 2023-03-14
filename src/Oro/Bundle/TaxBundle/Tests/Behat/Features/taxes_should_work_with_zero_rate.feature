@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroTaxBundle:taxes_should_work_with_zero_rate.yml
@ticket-BB-12335

Feature: Taxes should work with zero rate
  In order to be able to make purchases
  As a buyer
  I want to be able to create orders with products that have taxes with zero rate

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator
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

  Scenario: Order created successfully with tax with zero rate
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And There are products in the system available for order
    When I open page with shopping list List 1
    And I click "Create Order"
    Then I should not see "500 Internal Server Error"
    And I should see "Billing information"
    And I click "Ship to This Address"
    And I click "Continue"
    Then I should see Checkout Totals with data:
      | Subtotal | $10.00 |
      | Shipping | $3.00  |
    And I should not see "Tax"
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
