@ticket-BB-7459
@ticket-BB-25830
@automatically-ticket-tagged
@fixture-OroSaleBundle:QuoteOrder.yml
@fixture-OroSaleBundle:start_checkout_for_a_quote.yml
Feature: Start checkout for a quote
  In order to provide customers with ability to quote products
  As customer
  I need to be able to start checkout for a quote

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Login as administrator
    Given I proceed as the Admin
    And I login as administrator

  Scenario Outline: Add Multiple Files field to Contact Request entity
    Given I go to System/Entities/Entity Management
    And I filter Name as is equal to "<Entity>"
    And I click View <Entity> in grid
    When I click "Create Field"
    And I fill form with:
      | Field Name   | custom_multiple_files |
      | Storage Type | Table column          |
      | Type         | Multiple Files        |
    And I click "Continue"
    And I fill form with:
      | Label          | Custom Multiple Files |
      | File Size (MB) | 10                    |
    And I save and close form
    Then I should see "Field saved" flash message
    Examples:
      | Entity   |
      | Checkout |
      | Quote    |

  Scenario: Update schema
    Given I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Create RFQ
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    And I open page with shopping list Shopping List 1
    And I click "More Actions"
    And I click "Request Quote"
    And I fill form with:
      | PO Number | PONUMBER1 |
    And I click "Submit Request"

  Scenario: Create quote from RFQ
    Given I proceed as the Admin
    And I go to Sales / Requests For Quote
    When I click view "PONUMBER1" in grid
    And I click "Create Quote"
    And I fill "Quote Form" with:
      | LineItemPrice    | 5                                            |
      | Shipping Address | ORO, 801 Scenic Hwy, HAINES CITY FL US 33844 |
    And I click on "Calculate Shipping"
    And I save and close form
    Then I should see "Quote has been saved" flash message
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
