@ticket-BB-15985
@fixture-OroRFPBundle:RFQCustomer.yml
Feature: Frontend rfq product grid filters hint is appears after reloading page
  In order to see "Status" filter hint after reloading rfq page
  As a customer
  I add the "Status" filter and change value ​​and reload the page

  Scenario: Feature background
    Given I login as AmandaRCole@example.org buyer
    And I follow "Account"
    And I click "Requests For Quote"
    And I click "New Quote"
    And I fill form with:
      | First Name    | Amanda                  |
      | Last Name     | Cole                    |
      | Email Address | AmandaRCole@example.org |
      | Phone Number  | 72 669 62 82            |
      | Company       | Red Fox Tavern          |
      | Role          | Sauce cook              |
      | PO Number     | PO Test 01              |
      | Assigned To   | Amanda Cole             |
    And I click "Submit Request"

  Scenario: Check that the selected filter appears after reloading the page
    Given I click "Requests For Quote"
    And I show filter "Status" in frontend grid
    And I choose filter for Status as is any of "Submitted"
    When I reload the page
    Then I should see filter hints in frontend grid:
      | Status: is any of "Submitted" |
