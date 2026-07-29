@ticket-BB-27516
@regression
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Checkout.yml
@fixture-OroCheckoutBundle:CheckoutQuoteEurFixture.yml

Feature: Checkout from quote with products in different currencies
  In order to understand why a quote could not be turned into an order
  As a buyer
  I want to see a clear message when quote products use a different currency than my own

  Scenario: Enable Single Page Checkout Workflow
    Given There is USD currency in the system configuration
    And I login as administrator

  Scenario: Set internal status "Sent to Customer" for Quote with PO number "PO1"
    Given go to Sales/Quotes
    And click view PO1 in grid
    When I click "Send to Customer"
    And click "Send"
    Then I should see "Quote #1 successfully sent to customer" flash message

  Scenario: Set internal status "Sent to Customer" for Quote with PO number "PO2"
    Given go to Sales/Quotes
    And click view PO2 in grid
    When I click "Send to Customer"
    And click "Send"
    Then I should see "Quote #2 successfully sent to customer" flash message

  Scenario: Set internal status "Sent to Customer" for Quote with PO number "PO3"
    Given go to Sales/Quotes
    And click view PO3 in grid
    When I click "Send to Customer"
    And click "Send"
    Then I should see "Quote #3 successfully sent to customer" flash message

  Scenario: Accept quote with all products in a different currency
    Given AmandaRCole@example.org customer user has Buyer role
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    And I click "Quotes"
    And I click view PO2 in grid
    When I click "Accept and Submit to Order"
    And I click "Checkout"
    Then I should see "No products were added to the checkout because they use a different currency. Checkout supports only one currency at a time. Switch to another currency and try again." flash message

  Scenario: Accept quote with some products in a different currency
    Given I click "Account Dropdown"
    And I click "Quotes"
    And I click view PO3 in grid
    When I click "Accept and Submit to Order"
    And I click "Checkout"
    Then I should see "Some products were not added to the checkout because they use a different currency. Checkout supports only one currency at a time. Switch to another currency to purchase those items." flash message

  Scenario: Accept quote with matching currency proceeds without a currency warning
    Given I click "Account Dropdown"
    And I click "Quotes"
    And I click view PO1 in grid
    When I click "Accept and Submit to Order"
    And I click "Checkout"
    Then I should not see "No products were added to the checkout because they use a different currency. Checkout supports only one currency at a time. Switch to another currency and try again." flash message
    And I should not see "Some products were not added to the checkout because they use a different currency. Checkout supports only one currency at a time. Switch to another currency to purchase those items." flash message
    And I should see "Checkout"
