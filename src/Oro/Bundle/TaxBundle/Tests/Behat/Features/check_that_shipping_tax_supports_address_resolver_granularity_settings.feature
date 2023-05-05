@regression
@ticket-BB-20328
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroTaxBundle:ProductAndTaxes.yml

Feature: Check that shipping tax supports address resolver granularity settings
  In order to be able to manage tax restrictions
  As an administrator
  I create a tax restricted for the country and verify that they apply equally to both the product and shipping

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Configure address resolver granularity
    Given I proceed as the Admin
    And login as administrator
    And go to System/Configuration
    And follow "Commerce/Taxation/Tax Calculation" on configuration sidebar
    And uncheck "Use default" for "Address Resolver Granularity" field
    And uncheck "Use default" for "Use as Base by Default" field
    And fill "Tax Calculation Form" with:
      | Use As Base By Default       | Destination  |
      | Address Resolver Granularity | Only Country |
    And save form
    Then I should see "Configuration saved" flash message
    And follow "Commerce/Taxation/Shipping" on configuration sidebar
    And uncheck "Use default" for "Tax Code" field
    When I fill "Tax Shipping Form" with:
      | Tax Code | Product tax code |
    And save form
    Then I should see "Configuration saved" flash message

  Scenario: Check if the shipping tax is in effect across the country
    Given I proceed as the Buyer
    And I signed in as MarleneSBradley@example.org on the store frontend
    When I open page with shopping list Shopping List
    And press "Create Order"
    And I check "Ship to this address" on the checkout page
    And click "Continue"
    Then I should see "Subtotal $40.00"
    And should see "Shipping $3.00"
    And should see "Tax $4.30"
    And should see "Total $47.30"

  Scenario: Add restrictions to the tax for the country and the region
    Given I proceed as the Admin
    And go to Taxes/ Tax Jurisdictions
    And click edit "TaxJurisdiction" in grid
    When I fill form with:
      | State | Florida |
    And save and close form
    Then I should see "Tax Jurisdiction has been saved" flash message

  Scenario: Check that taxes do not apply to product and shipping
    Given I proceed as the Buyer
    And I reload the page
    Then I should see "Subtotal $40.00"
    And should see "Shipping $3.00"
    And should not see "Tax $0.30"
    And should see "Total $43.00"
