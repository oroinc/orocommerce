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

  Scenario: Enable Single Page Checkout Workflow
    Given There is USD currency in the system configuration
    And I login as administrator
    And go to System/Workflows
    When I click "Activate" on row "Single Page Checkout" in grid
    And I click "Activate"
    Then I should see "Workflow activated" flash message

  Scenario: Set internal status "Sent to Customer" for Quote with PO number "PO1"
    Given go to Sales/Quotes
    And click view PO1 in grid
    When I click "Send to Customer"
    And click "Send"
    Then I should see "Quote #1 successfully sent to customer" flash message

  Scenario: Create order from Quote PO1
    Given AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account"
    And I click "Quotes"
    And I click view PO1 in grid
    When I click "Accept and Submit to Order"
    And I click "Submit"
    Then I should see "There is not enough quantity for this product"

    When I select "Fifth avenue, 10115 Berlin, Germany" from "Select Billing Address"
    And I select "Fifth avenue, 10115 Berlin, Germany" from "Select Shipping Address"
    And I check "Flat Rate" on the checkout page
    And I check "Payment Terms" on the checkout page
    And I click "Submit Order"
    Then I should see "There was an error while processing the order"
    And I should see "There is not enough quantity for this product"
