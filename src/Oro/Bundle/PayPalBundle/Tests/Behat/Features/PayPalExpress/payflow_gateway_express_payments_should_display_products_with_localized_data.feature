@regression
@ticket-BB-15562
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPayPalBundle:PayPalExpressProduct.yml
Feature: Payflow Gateway Express payments should display products with localized data
  In order to be able to see localized products in the payment
  As a buyer
  I want to be able to create pay and see product name and description in the payment,
  in the localization that used in current moment

  Scenario: Feature Background
    Given I enable the existing localizations
    And There are products in the system available for order
    And I create PayPal PaymentsPro integration with following settings:
      | creditCardPaymentAction | charge |
      | zeroAmountAuthorization | true   |
    And I create payment rule with "ExpressPayPal" payment method

  Scenario: Successful first order payment with  PayPal PayFlow Gateway
    Given I login as AmandaRCole@example.org the "Buyer" at "first_session" session
    And I am on the homepage
    And I click "Localization Switcher"
    And I select "Zulu" localization
    And I open page with shopping list List 1
    And I click "Create Order"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "ExpressPayPal" on the "Payment" checkout step and press Continue
    When I click "Submit Order"
    Then I should see the following products before pay:
      | NAME              | DESCRIPTION     |
      | SKU123 ZU product | ZU product desc |
    And I see the "Thank You" page with "Thank You For Your Purchase!" title
