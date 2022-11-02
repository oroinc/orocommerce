@regression
@ticket-BB-16238
@fixture-OroSaleBundle:QuoteBackofficeApprovalsFixture.yml
Feature: Backoffice quote create quote without review and approve permission
  In order to use Backoffice Quote Flow with Approvals
  As a backend user without the Review And Approve Quotes permission
  I want to see Submit for Review button after changing product price in newly created quote

  Scenario: Create window sessions
    Given sessions active:
      | Admin   | first_session  |
      | Manager | second_session |
    And I proceed as the Admin
    And I login as administrator

  Scenario: Check workflow permissions for user
    Given I go to System/User Management/Roles
    And I filter Label as is equal to "Sales Rep"
    And I click Edit Sales Rep in grid
    And I check "Override quote prices" entity permission
    And I check "Add free-form items" entity permission
    And I uncheck "Review and approve quotes" entity permission
    And I save and close form
    Then I should see "Role saved" flash message

  Scenario: Create quote with default price
    Given I proceed as the Manager
    And I login as "john" user
    When I go to Sales/Quotes
    And I click "Create Quote"
    And I fill "Quote Form" with:
      | Customer        | first customer |
      | LineItemProduct | AA1            |
    And I save and close form
    And agree that shipping cost may have changed
    Then I should see "Quote has been saved" flash message
    And "Send to Customer" button is not disabled
    And I should not see following buttons:
      | Submit for Review |
    And I should see "AA1 Product1 1 item or more $2.00"

  Scenario: Create quote with non-default price
    When I go to Sales/Quotes
    And I click "Create Quote"
    And I fill "Quote Form" with:
      | Customer        | first customer |
      | LineItemProduct | AA1            |
    And I type "10" in "LineItemPrice"
    And I save and close form
    And agree that shipping cost may have changed
    Then I should see "Quote has been saved" flash message
    And "Send to Customer" button is disabled
    And "Submit for Review" button is not disabled
    And I should see "AA1 Product1 1 item or more $10.00"

  Scenario: Edit quote with non-default price
    When I click "Edit"
    And I fill "Quote Form" with:
      | LineItemQuantity | 15 |
      | LineItemPrice    | 12 |
    And I click "Submit"
    And agree that shipping cost may have changed
    Then I should see "AA1 Product1 15 items or more $12.00"
    And "Send to Customer" button is disabled
    And "Submit for Review" button is not disabled

  Scenario: Edit quote without changing the price price
    When I click "Edit"
    And I click "Add Notes"
    And I type "Quote line item note" in "LineItemNote"
    And I click on empty space
    And I click "Submit"
    Then I should see "AA1 Product1 15 items or more $12.00 Quote line item note"
    And "Send to Customer" button is disabled
    And "Submit for Review" button is not disabled

  Scenario: Create quote with free form product
    When I go to Sales/Quotes
    And I click "Create Quote"
    And I fill "Quote Form" with:
      | Customer | first customer |
    And I click "Free-form"
    And I click "Add Offer"
    And I fill "Quote Form" with:
      | LineItemPrice           | 10  |
      | LineItemFreeFormSku     | AA1 |
      | LineItemFreeFormProduct | AA1 |
    And I save and close form
    And agree that shipping cost may have changed
    Then I should see "Quote has been saved" flash message
    And "Send to Customer" button is disabled
    And "Submit for Review" button is not disabled

  Scenario: Submit for Review quote
    When click "Submit for Review"
    And I type "Please approve" in "Comment"
    And click "Submit"
    And should see "Quote #33 successfully submitted for review" flash message
    Then I should not see following buttons:
      | Submit for Review |
    When I proceed as the Admin
    And go to Sales/Quotes
    And filter "Quote #" as is equal to "33"
    And click Review "33" in grid
    And click "Yes"
    And click Approve "33" in grid
    And I type "Approved" in "Comment"
    And click "Submit"
    And I proceed as the Manager
    And reload the page
    Then "Send to Customer" button is not disabled
