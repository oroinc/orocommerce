@ticket-BB-10457
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
@fixture-OroSaleBundle:QuoteToOrderFixture.yml

Feature: Convert Quote without customer user assignment to order
  In order to process quotes on front store
  As a Frontend admin
  I need to be able to convert quote to order when quote was assigned only to Customer

  Scenario: Feature Background
    Given I login as administrator
    And I go to Sales / Quotes
    When I click "Send to Customer" on row "Q123" in grid
    And I fill form with:
      | To | NancyJSallee@example.org |
    And I click "Send"
    Then I should see "Quote #1 successfully sent to customer" flash message

  Scenario: Create order from quote
    Given I signed in as NancyJSallee@example.org on the store frontend
    And I follow "Account"
    And I click "Quotes"
    And I click view Q123 in grid
    When I click "Accept and Submit to Order"
    And I click "Submit"
    Then Checkout "Order Summary Products Grid" should contain products:
      | Product1 | 5 | items |
