@ticket-BB-7459
@automatically-ticket-tagged
@fixture-OroSaleBundle:QuoteOrder.yml
Feature: Start checkout for a quote
  In order to provide customers with ability to quote products
  As customer
  I need to be able to start checkout for a quote

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: FrontOffice scenario background
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I open page with shopping list Shopping List 1
    And I click "More Actions"
    And I click "Request Quote"
    And I fill form with:
      | PO Number | PONUMBER1 |
    And I click "Submit Request"

  Scenario: BackOffice scenario background
    Given I proceed as the Admin
    And I login as administrator
    And I create a quote from RFQ with PO Number "PONUMBER1"
    And I click "Send to Customer"
    And I click "Send"

  Scenario: Verify "All Quotes" grid
    Given I proceed as the Buyer
    And I am on homepage
    When I click "Quotes"
    Then I shouldn't see "Status" column in frontend grid
    And I shouldn't see "Status" filter in frontend grid
    When I show column "Status" in frontend grid
    And I show filter "Status" in frontend grid
    Then I should see "Status" column in frontend grid
    And I should see "Status" filter in frontend grid

  Scenario: Start checkout
    Given I click view PONUMBER1 in grid
    And I click "Accept and Submit to Order"
    And I click "Submit"
    Then Buyer is on enter billing information checkout step
