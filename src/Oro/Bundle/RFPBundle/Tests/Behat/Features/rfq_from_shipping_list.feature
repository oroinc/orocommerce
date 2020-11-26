@fixture-OroShoppingListBundle:ShoppingListFixture.yml
@ticket-BB-9841
@ticket-BB-12064
@ticket-BB-14232
@ticket-BB-16591
@ticket-BB-16921
Feature: RFQ from Shipping List
  In order to ...
  As an ...
  I should be able to ...

  Scenario: Create different window session
    Given sessions active:
      | User  |first_session |
      | Admin |second_session|

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
      | First Name             | Marlene                                                               |
      | Last Name              | Bradley                                                               |
      | Email Address          | MarleneSBradley@example.com                                           |
      | Phone Number           | 72 669 62 82                                                          |
      | Company                | Red Fox Tavern                                                        |
      | Role                   | Sauce cook                                                            |
      | PO Number              | PO Test 01                                                            |
      | Assigned To            | Marlene Bradley                                                       |
      | Do Not Ship Later Than | 7/1/2018                                                                |

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
      | Do Not Ship Later Than | 7/1/2018                                                                |

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
      | LineItemPrice    | 12 |
    And save and close form
    And click "Save on conf window"
    And click "Line Items"
    Then should see "This item was missed in the previous request"
    And click "Send to Customer"
    And click "Send"
    And I should see "Quote #1 successfully sent to customer" flash message
    When I proceed as the User
    And I follow "Account"
    And click "Quotes"
    And click view "PO Test 01" in grid
    Then should see "My Notes: This item was missed in the previous request"

  Scenario: Create Order from RFQ with note, PO Number and ship until
    When I proceed as the Admin
    And I go to Sales/ Requests For Quote
    And I click view "PO Test 01" in grid
    And I click on "RFQ Create Order"
    Then "Order Form" must contains values:
      | PO Number              | PO Test 01                                                            |
      | Do Not Ship Later Than | Jul 1, 2018                                                           |
      | Customer Notes         | Parish so enable innate in formed missed. Hand two was eat busy fail. |
