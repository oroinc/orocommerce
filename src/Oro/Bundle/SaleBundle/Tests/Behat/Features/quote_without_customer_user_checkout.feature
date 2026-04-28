@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroSaleBundle:QuoteWithoutCustomerUserFixture.yml

Feature: Quote without customer user checkout
  In order to process quotes on front store
  As a Frontend admin
  I need to be able to convert quote to order when quote was assigned only to Customer without customer user

  Scenario: Send quote to customer user
    Given I login as administrator
    And I go to Sales / Quotes
    When I click "Send to Customer" on row "Q123" in grid
    And I fill form with:
      | To | NancyJSallee@example.org |
    And I click "Send"
    Then I should see "Quote #1 successfully sent to customer" flash message

  Scenario: Accept quote and complete checkout
    Given I signed in as NancyJSallee@example.org on the store frontend
    And I click "Account Dropdown"
    And I click "Quotes"
    And I click view Q123 in grid
    When I click "Accept and Submit to Order"
    And I click "Checkout"
    Then Buyer is on enter billing information checkout step
    And I select "ORO, 801 Scenic Hwy, HAINES CITY FL US 33844" on the "Billing Information" checkout step and press Continue
    And I select "ORO, 801 Scenic Hwy, HAINES CITY FL US 33844" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    Then Checkout "Order Summary Products Grid" should contain products:
      | Product1 | 5 | items |
    When I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
