@ticket-BB-11387
@fixture-OroRFPBundle:RFQCustomer.yml

Feature: RFQ with empty product
  In order to ask for Quote
  As a User
  I want to be able to create RFQ

  Scenario: Create RFQ with empty product
    Given I login as AmandaRCole@example.org buyer
    And I follow "Account"
    And I click "Requests For Quote"
    And I click "New Quote"
    And I fill form with:
      | First Name    | Amanda                                                                |
      | Last Name     | Cole                                                                  |
      | Email Address | AmandaRCole@example.org                                               |
      | Phone Number  | 72 669 62 82                                                          |
      | Company       | Red Fox Tavern                                                        |
      | Role          | Sauce cook                                                            |
      | Notes         | Parish so enable innate in formed missed. Hand two was eat busy fail. |
      | PO Number     | PO Test 01                                                            |
      | Assigned To   | Amanda Cole                                                           |

    When I click "Submit Request"
    Then I should see RFQ with data:
      | First Name    | Amanda                                                                |
      | Last Name     | Cole                                                                  |
      | Email Address | AmandaRCole@example.org                                               |
      | Phone Number  | 72 669 62 82                                                          |
      | Company       | Red Fox Tavern                                                        |
      | Role          | Sauce cook                                                            |
      | Notes         | Parish so enable innate in formed missed. Hand two was eat busy fail. |
      | PO Number     | PO Test 01                                                            |
      | Assigned To   | Amanda Cole                                                           |
