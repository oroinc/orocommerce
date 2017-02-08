@fixture-QuoteOrder.yml
Feature: Start checkout for a quote
  In order to provide customers with ability to quote products
  As customer
  I need to be able to start checkout for a quote

  Scenario: "Quote 1" > START CHECKOUT ON A QUOTE BASED ON CREATED RFQ. PRIORITY - CRITICAL
    Given I login as AmandaRCole@example.org buyer
    And I open page with shopping list Shopping List 1
    And I click "Request Quote"
    And I fill in "PO Number" with "PONUMBER1"
    And I click "Submit Request"
    And Admin creates a quote for RFQ with PO Number "PONUMBER1"
    When I click "Quotes"
    And Buyer starts checkout for a quote with "PONUMBER1" PO Number
    Then Buyer is on enter billing information checkout step
