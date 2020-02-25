@ticket-BB-10029
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:SmallInventoryLevel.yml
@community-edition-only

Feature: Single Page Checkout From Quote Quantity Errors
  In order to to create order from Quote on front store
  As a buyer
  I want to start checkout from Quote view page and view quantity validation errors before submit order

  Scenario: Feature Background
    Given There is USD currency in the system configuration
    And I activate "Single Page Checkout" workflow

  Scenario: Set internal status "Sent to Customer" for Quote with PO number "PO1"
    Given I login as administrator
    And go to Sales/Quotes
    And click view PO1 in grid
    When I click "Send to Customer"
    And click "Send"
    Then I should see "Quote #1 successfully sent to customer" flash message

  Scenario: Create order from Quote PO1
    Given AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    And I follow "Account"
    And I click "Quotes"
    And I click view PO1 in grid
    When I click "Accept and Submit to Order"
    And I click "Submit"
    Then I should see "There is not enough quantity for this product"

    When I select "Fifth avenue, 10115 Berlin, Germany" from "Select Billing Address"
    And I select "Fifth avenue, 10115 Berlin, Germany" from "Select Shipping Address"
    And I check "Flat Rate" on the checkout page
    And I check "Payment Terms" on the checkout page
    Then I should see "There is not enough quantity for this product"
    And I should see "Submit Order" button disabled
