@fixture-QuoteOrder.yml
Feature: Start checkout for a quote
  In order to provide customers with ability to quote products
  As customer
  I need to be able to start checkout for a quote

  Scenario: "Quote 1" > START CHECKOUT ON A QUOTE BASED ON CREATED RFQ. PRIORITY - CRITICAL
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I request a quote from shopping list "Shopping List 1" with data:
      | PO Number | PONUMBER1 |

    And I login as administrator
    And I create a quote from RFQ with PO Number "PONUMBER1"
    And I click "Send to Customer"
    And I click "Send"

    And I login as AmandaRCole@example.org buyer
    When I click "Quotes"
    And Buyer starts checkout for a quote with "PONUMBER1" PO Number
    Then Buyer is on enter billing information checkout step
