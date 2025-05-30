@ticket-BB-7459
@ticket-BB-25200
@automatically-ticket-tagged
@fixture-OroFlatRateShippingBundle:FlatRateIntegration.yml
@fixture-OroPaymentTermBundle:PaymentTermIntegration.yml
@fixture-OroCheckoutBundle:Payment.yml
@fixture-OroCheckoutBundle:Shipping.yml
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
    And I click "Account Dropdown"
    When I click "Requests For Quote"
    And I click view "PONUMBER1" in grid
    Then I should see "Quotes Quote #1"
    And I click "Account Dropdown"
    When I click "Quotes"
    Then I shouldn't see "Status" column in frontend grid
    And I shouldn't see "Status" filter in frontend grid
    When I show column "Status" in frontend grid
    And I show filter "Status" in frontend grid
    Then I should see "Status" column in frontend grid
    And I should see "Status" filter in frontend grid

  Scenario: Start checkout
    Given I click view "PONUMBER1" in grid
    Then I should see "Request Request For Quote #1"
    And I click "Accept and Submit to Order"
    And I click "Checkout"
    And Buyer is on enter billing information checkout step

  Scenario: Check Quote grid in the RFQ view page
    Given I proceed as the Admin
    And I go to Sales/ Requests For Quote
    When I click view "PONUMBER1" in grid
    Then I should see following "RFQ Quote Grid" grid containing rows:
      | Quote # | Internal Status  | PO Number |
      | 1       | Sent to Customer | PONUMBER1 |

  Scenario: Check that there is no links for the deleted entities
    Given I click "Delete"
    And I proceed as the Buyer
    And I am on homepage
    And I click "Account Dropdown"
    When I click "Quotes"
    And I click view "PONUMBER1" in grid
    Then I should not see "Request Request For Quote #1"
    When I proceed as the Admin
    And I click "Undelete"
    And I go to Sales/ Quotes
    And I click delete "PONUMBER1" in grid
    And I click "Yes"
    And I proceed as the Buyer
    And I am on homepage
    And I click "Account Dropdown"
    And  I click "Requests For Quote"
    And I click view "PONUMBER1" in grid
    Then I should not see "Quotes Quote #1"
    When I proceed as the Admin
    And I go to Sales/ Requests For Quote
    And I click view "PONUMBER1" in grid
    Then I should see following "RFQ Quote Grid" grid containing rows:
      | Quote # | Internal Status | PO Number |
      | 1       | Deleted         | PONUMBER1 |


