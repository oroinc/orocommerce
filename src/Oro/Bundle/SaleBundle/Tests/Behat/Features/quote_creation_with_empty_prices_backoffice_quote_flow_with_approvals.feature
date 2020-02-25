@ticket-BB-14734
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
@fixture-OroSaleBundle:QuoteProductFixture.yml
Feature: Quote creation with empty prices (Backoffice Quote Flow with Approvals)
  In order to have possibility to create quote always
  As an Administrator
  I want to have ability to have quote created even if no price was set

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create request for quote with empty and zero price as buyer
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I follow "Account"
    And I click "Requests For Quote"
    And I click "New Quote"
    And I fill form with:
      |PO Number|PO1|

    And fill "Frontstore RFQ Line Item Form1" with:
      |SKU         |psku1        |
      |Quantity    |1            |
    And click "Update Line Item"

    And click "Add Another Product"
    And fill "Frontstore RFQ Line Item Form2" with:
      |SKU         |psku1        |
      |Quantity    |1            |
      |Target Price|0            |
    And click "Update Line Item"
    And click "Submit Request"
    Then should see "Request has been saved" flash message

  Scenario: Create quote with empty/zero price from RFQ and check send to customer is not available
    Given I proceed as the Admin
    And I login as administrator
    And go to Sales/ Requests For Quote
    When click view "PO1" in grid
    When click "Create Quote"
    And save and close form
    And I should see "Send to Customer"
    And "Send to Customer" button is disabled
    And I should see "Draft"

  Scenario: Set price to quote line item and check quote can be sent to customer
    Given I click "Edit"
    And I fill "Quote Form" with:
      | LineItemPrice | 1 |
    And I wait 2 seconds until submit button becomes available
    And I click "Submit"
    And I click "Save" in modal window
    Then I should see "Quote #1 successfully updated" flash message
    And I should see "Send to Customer"
    And "Send to Customer" button is not disabled
    And click "Send to Customer"
    And click "Send"
    Then I should see Quote with:
      | Quote #         | 1                |
      | PO Number       | PO1              |
      | Internal Status | Sent to customer |
      | Customer Status | N/A              |
    And should see following "Quote Line Item Grid" grid:
      | SKU   | Product  | Quantity       | Price |
      | psku1 | Product1 | 1 item or more | $1.00 |
      | psku1 | Product1 | 1 item or more | $0.00 |

  Scenario: Customer can create order from Quote with zero price for line item
    Given I proceed as the Buyer
    When click "Quotes"
    And click view "PO1" in grid
    And should see following "Quote View Grid" grid:
      | Item                  | Quantity       | Unit Price |
      | Product1 SKU #: psku1 | 1 item or more | $1.00      |
      | Product1 SKU #: psku1 | 1 item or more | $0.00      |
    When click "Accept and Submit to Order"
    And click "Submit"
    Then Page title equals to "Billing Information - Checkout"
    And Checkout "Order Summary Products Grid" should contain products:
      | Product1 | 1 | item |
      | Product1 | 1 | item |
    And I should see Checkout Totals with data:
      | Subtotal | $1.00 |
