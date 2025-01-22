@ticket-BB-24949
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
@fixture-OroSaleBundle:QuoteToOrderFixture.yml

Feature: Quote Choice "Checkout" button on Mobile
  In order to verify "Checkout" button after recalculating totals on Mobile View on the Quote Choice page
  Ensure that the "Checkout" button remains accessible, allowing users to proceed with order creation.

  Scenario: Feature Background
    Given I login as administrator
    And I go to Sales / Quotes
    When I click "Send to Customer" on row "Q123" in grid
    And I fill form with:
      | To | AmandaRCole@example.org |
    And I click "Send"
    Then I should see "Quote #1 successfully sent to customer" flash message

  Scenario: Create order from quote
    Given I login as AmandaRCole@example.org the "Buyer" at "640_session" session
    And I set window size to 640x1100
    And I click "Account Dropdown"
    And I click "Quotes"
    And I click view Q123 in grid
    And I click "Customer TopBar Action Dropdown"
    And I click "Accept and Submit to Order"
    When I type "10" in "First Product Quantity on Quote"
    And I click on empty space
    Then I should see "Checkout"
    And I click "Checkout"
    And Page title equals to "Billing Information - Checkout"
