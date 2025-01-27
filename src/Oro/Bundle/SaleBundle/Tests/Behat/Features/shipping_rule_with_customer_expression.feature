@regression
@ticket-BB-16388
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroFlatRateShippingBundle:FlatRate2Integration.yml
@fixture-OroCheckoutBundle:ShippingRuleForFlatRate2.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroSaleBundle:shipping_selector_quote.yml
Feature: Shipping Rule With Customer Expression
  In order to select the most suitable shipping method for a quote
  As administrator
  I want to see shipping methods for rules which has customer expression

  Scenario: Create a quote and finished checkout with Flat Rate Shipping Method
    Given I login as administrator
    And I go to System/ Shipping Rules
    And I click Edit "Flat Rate 2$" in grid
    When fill "Shipping Rule" with:
      | Expression | customer.id > 0 and customerUser.id > 0 |
    And I save and close form
    Then should see "Shipping rule has been saved" flash message

  Scenario: Create a quote and finished checkout with Flat Rate Shipping Method
    And I go to Sales/ Quotes
    And I click "Create Quote"
    And I fill "Quote Form" with:
      | Customer         | Company A                               |
      | Customer User    | Amanda Cole                                  |
      | LineItemProduct  | SKU123                                       |
      | Shipping Address | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
    And click "Shipping Information"
    And I click on "Calculate Shipping"
    And I should see "Flat Rate $3.00"
    And I should see "Flat Rate 2 $2.00"
    And I save and close form
    And I should see "Quote has been saved" flash message
