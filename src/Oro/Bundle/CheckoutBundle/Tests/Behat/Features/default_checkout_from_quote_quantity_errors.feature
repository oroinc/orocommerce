@ticket-BB-8592
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:SmallInventoryLevel.yml
@community-edition-only

Feature: Default Checkout From Quote Quantity Errors
  In order to to create order from Quote on front store
  As a buyer
  I want to start checkout from Quote view page and view quantity validation errors before submit order

  Scenario: Set internal status "Sent to Customer" for Quote with PO number "PO1"
    Given I login as administrator
    And go to Sales/Quotes
    And click view PO1 in grid
    When I click "Send to Customer"
    And click "Send"
    Then I should see "Quote #1 successfully sent to customer" flash message

  Scenario: Create order from Quote PO1
    Given There is USD currency in the system configuration
    And AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    And I follow "Account"
    And I click "Quotes"
    And I click view PO1 in grid

    When I click "Accept and Submit to Order"
    And I click "Submit"
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Billing Information" checkout step and press Continue
    And I select "Fifth avenue, 10115 Berlin, Germany" on the "Shipping Information" checkout step and press Continue
    And I check "Flat Rate" on the "Shipping Method" checkout step and press Continue
    And I check "Payment Terms" on the "Payment" checkout step and press Continue
    Then I should see "There is not enough quantity for this product"
    And I should see "Submit Order" button disabled
