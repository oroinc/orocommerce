@fixture-OroRFPBundle:RFQCustomer.yml
@ticket-BB-16463
Feature: Create RFQ on storefront

  Scenario: Feature background
    Given sessions active:
      | Buyer | first_session  |
      | Admin | second_session |

  Scenario: Create RFQ that contains notes
    Given I continue as the Buyer
    When I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account"
    And I click "Requests For Quote"
    And I click "New Quote"
    And I fill "Frontend Request Form" with:
      | First Name | First Name    |
      | Last Name  | Last Name     |
      | Company    | Company New   |
      | Notes      | <h1>note</h1> |
      | PoNumber   | 007           |
    And I click "Submit Request"
    Then I should see "Request has been saved" flash message

    When I continue as the Admin
    And I login as administrator
    And I go to Sales/Requests For Quote
    And I click view 007 in grid
    Then I should see RFQ with:
      | First Name | First Name    |
      | Last Name  | Last Name     |
      | Company    | Company New   |
      | Notes      | <h1>note</h1> |
      | PO Number  | 007           |
