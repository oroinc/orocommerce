@regression
@ticket-BB-11598
@automatically-ticket-tagged
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:InventoryLevel.yml

Feature: Checkout from quote with deleted product
  In order to create order on front store
  As a buyer
  I want to start and complete checkout from quote with a deleted product

  Scenario: Set internal status "Sent to Customer" for Quote with PO number "PO1"
    Given There is USD currency in the system configuration
    And I login as administrator
    Given go to Sales/Quotes
    And click view PO1 in grid
    When I click "Send to Customer"
    And click "Send"
    Then I should see "Quote #1 successfully sent to customer" flash message

  Scenario: Delete the product from the system
    Given go to Products/Products
    And click Delete SKU123 in grid
    And click "Yes, Delete"

  Scenario: Create order from Quote PO1
    Given AmandaRCole@example.org customer user has Buyer role
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
    And I click "Submit Order"
    Then I see the "Thank You" page with "Thank You For Your Purchase!" title
