@fixture-OroSaleBundle:QuoteBackofficeDefaultFixture.yml
@fixture-OroCustomerBundle:CustomerUserAddressMarleneFixture.yml
@ticket-BB-9841
@ticket-BB-12064
@ticket-BB-14232
@ticket-BB-16591
@ticket-BB-16921
@ticket-BB-21324
@waf-skip
Feature: RFQ from Shipping List

  Scenario: Create different window session
    Given sessions active:
      | User  | first_session  |
      | Admin | second_session |

  Scenario: Create RFQ from shopping list by Marlene
    Given I proceed as the User
    And I signed in as MarleneSBradley@example.com on the store frontend
    And I open page with shopping list "Shopping List 4"
    And I click "Add a note to entire Shopping List"
    And I type "Parish so enable innate in formed missed. Hand two was eat busy fail." in "Shopping List Notes"
    And I click on "Save Shopping List Notes"
    Then I should see "Parish so enable innate in formed missed. Hand two was eat busy fail."
    And I click "Add Shopping List item Note" on row "AA1" in grid
    When I fill in "Shopping List Product Note" with "This item was missed in the previous request"
    And I click "Add"
    Then I should see "Line item note has been successfully updated" flash message
    And I click "More Actions"
    When I click "Request Quote"
    Then the "Request Notes" field element should contain "Parish so enable innate in formed missed. Hand two was eat busy fail."
    And I should see "Note: This item was missed in the previous request"
    And I fill "Frontend Request Form" with:
      | First Name             | Marlene                     |
      | Last Name              | Bradley                     |
      | Email Address          | MarleneSBradley@example.com |
      | Phone Number           | 72 669 62 82                |
      | Company                | Red Fox Tavern              |
      | Role                   | Sauce cook                  |
      | PO Number              | PO Test 01                  |
      | Assigned To            | Marlene Bradley             |
      | Do Not Ship Later Than | 7/1/2018                    |
    And click "Edit RFQ Line Item"
    And fill "Frontstore RFQ Line Item Form1" with:
      | Target Price | 1 |
    And click "Update Line Item"
    Then I should see "Product1 QTY: 10 item Target Price $1.00 Listed Price: $5.00"

    When I click "Submit Request"
    Then I should see RFQ with data:
      | First Name             | Marlene                                                               |
      | Last Name              | Bradley                                                               |
      | Email Address          | MarleneSBradley@example.com                                           |
      | Phone Number           | 72 669 62 82                                                          |
      | Company                | Red Fox Tavern                                                        |
      | Role                   | Sauce cook                                                            |
      | Notes                  | Parish so enable innate in formed missed. Hand two was eat busy fail. |
      | PO Number              | PO Test 01                                                            |
      | Assigned To            | Marlene Bradley                                                       |
      | Do Not Ship Later Than | 7/1/2018                                                              |
    And I should see "Notes: This item was missed in the previous request"

  Scenario: Request more information(notes) field validation
    Given I proceed as the Admin
    And I login as administrator
    And I go to Sales/ Requests For Quote
    When I click view "PO Test 01" in grid
    Then I should see RFQ with:
      | Notes | Parish so enable innate in formed missed. Hand two was eat busy fail. |
    And I should see "This item was missed in the previous request"
    When I click "Request More Information"
    And I type "Provide More Information" in "Notes"
    And I click "Submit"
    And I should see "More Information Requested"
    And I proceed as the User
    And I follow "Account"
    And I click "Requests For Quote"
    And I click view "PO Test 01" in grid
    And I click "Provide More Information"
    And I type "<script>alert(1)</script>" in "Notes"
    And I click "Submit"
    And I proceed as the Admin
    And I reload the page
    Then I should see "alert(1)"

  Scenario: Create Quote from RFQ with note and line item note
    When I click "Create Quote"
    Then "Quote Form" must contains values:
      | PO Number              | PO Test 01  |
      | Do Not Ship Later Than | Jul 1, 2018 |
    When fill "Quote Form" with:
      | LineItemPrice | 12 |
    And save and close form
    And click "Save on conf window"
    And click "Line Items"
    Then should see "This item was missed in the previous request"
    And click "Send to Customer"
    And click "Send"
    And I should see "Quote #31 successfully sent to customer" flash message
    When I proceed as the User
    And I follow "Account"
    And click "Quotes"
    And click view "PO Test 01" in grid
    Then should see "My Notes: This item was missed in the previous request"

  Scenario: Create Order from RFQ with note, PO Number, ship until and requested qty and price
    When I proceed as the Admin
    And I go to Sales/ Requests For Quote
    And I click view "PO Test 01" in grid
    And I click on "RFQ Create Order"
    Then "Order Form" must contains values:
      | PO Number              | PO Test 01                                                            |
      | Do Not Ship Later Than | Jul 1, 2018                                                           |
      | Customer Notes         | Parish so enable innate in formed missed. Hand two was eat busy fail. |
      | Billing Address        | ORO, 2849 Junkins Avenue, ALBANY NY US 31707                          |
      | Shipping Address       | ORO, 2849 Junkins Avenue, ALBANY NY US 31707                          |
    And I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $2.00     | $2.00     | $0.00      |
      | Row Total  | $2.00     | $2.00     | $0.00      |
    When I click "Order Form Line Item 1 Offer 1"
    And I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $1.00     | $1.00     | $0.00      |
      | Row Total  | $10.00    | $10.00    | $0.00      |
    When I click "Order Totals"
    Then I should see "Subtotal $10.00"
    And I should see "Total $10.00"
    When I click "Line Items"
    And I click "Tier prices button"
    Then I should see "Click to select price per unit"
    And I should see "$2.00"
    When I click "$2.00"
    Then "Order Form" must contains values:
      | Price | 2.00 |
    When I click "Order Totals"
    Then I should see "Subtotal $20.00"
    And I should see "Total $20.00"
    When I save and close form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message

  Scenario: Create another RFQ from shopping list with more than one product
    Given I proceed as the User
    When I open page with shopping list "Shopping List 6"
    And I click "More Actions"
    And I click "Request Quote"
    And I fill "Frontend Request Form" with:
      | First Name             | Marlene                     |
      | Last Name              | Bradley                     |
      | Email Address          | MarleneSBradley@example.com |
      | Phone Number           | 72 669 62 82                |
      | Company                | Red Fox Tavern              |
      | Role                   | Sauce cook                  |
      | PO Number              | PO Test 02                  |
      | Assigned To            | Marlene Bradley             |
      | Do Not Ship Later Than | 7/1/2018                    |
    And click "Edit RFQ Line Item"
    And fill "Frontstore RFQ Line Item Form1" with:
      | Target Price | 3 |
    And click "Update Line Item"
    Then I should see "Product1 QTY: 3 item Target Price $3.00 Listed Price: $2.00"
    When click "Edit RFQ Line Item 2"
    And fill "Frontstore RFQ Line Item Form2" with:
      | Target Price | 30 |
    And click "Update Line Item"
    Then I should see "Product2 QTY: 4 item Target Price $30.00 Listed Price: $20.00"

    When I click "Submit Request"
    Then I should see RFQ with data:
      | First Name             | Marlene                     |
      | Last Name              | Bradley                     |
      | Email Address          | MarleneSBradley@example.com |
      | Phone Number           | 72 669 62 82                |
      | Company                | Red Fox Tavern              |
      | Role                   | Sauce cook                  |
      | PO Number              | PO Test 02                  |
      | Assigned To            | Marlene Bradley             |
      | Do Not Ship Later Than | 7/1/2018                    |

  Scenario: Create Order from the new RFQ to confirm that functions of line item section work well
    When I proceed as the Admin
    And I go to Sales/ Requests For Quote
    And I click view "PO Test 02" in grid
    And I click on "RFQ Create Order"
    Then "Order Form" must contains values:
      | PO Number              | PO Test 02  |
      | Do Not Ship Later Than | Jul 1, 2018 |
    When I click "Order Form Line Item 1 Offer 1"
    Then I should see next rows in "Backend Order First Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $3.00     | $3.00     | $0.00      |
      | Row Total  | $9.00     | $9.00     | $0.00      |
    When I click "Order Form Line Item 2 Offer 1"
    Then I should see next rows in "Backend Order Second Line Item Taxes Items Table" table
      |            | Incl. Tax | Excl. Tax | Tax Amount |
      | Unit Price | $30.00    | $30.00    | $0.00      |
      | Row Total  | $120.00   | $120.00   | $0.00      |
    When I click "Order Totals"
    Then I should see "Subtotal $129.00"
    And I should see "Total $129.00"
    When I save and close form
    And I click "Save" in modal window
    Then I should see "Order has been saved" flash message
