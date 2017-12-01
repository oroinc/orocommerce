@fixture-OroShoppingListBundle:ShoppingListFixture.yml
@ticket-BB-9841
@ticket-BB-12064
Feature: RFQ from Shipping List
  ToDo: BAP-16103 Add missing descriptions to the Behat features
  Scenario: Create different window session
    Given sessions active:
      | User  |first_session |
      | Admin |second_session|

  Scenario: Create RFQ from shopping list by Marlene
    Given I proceed as the User
    And I signed in as MarleneSBradley@example.com on the store frontend
    And I open page with shopping list "Shopping List 4"
    And I click "Request Quote"
    And I fill form with:
      | First Name             | Marlene                                                               |
      | Last Name              | Bradley                                                               |
      | Email Address          | MarleneSBradley@example.com                                           |
      | Phone Number           | 72 669 62 82                                                          |
      | Company                | Red Fox Tavern                                                        |
      | Role                   | Sauce cook                                                            |
      | Notes                  | Parish so enable innate in formed missed. Hand two was eat busy fail. |
      | PO Number              | PO Test 01                                                            |
      | Assigned To            | Marlene Bradley                                                       |

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

  Scenario: Request more information(notes) field validation
    Given I proceed as the Admin
    And I login as administrator
    And I go to Sales/ Requests For Quote
    And I click view "PO Test 01" in grid
    When I click "Request More Information"
    And I type "Provide More Information" in "Notes"
    And I click "Submit"
    And I should see "More Information Requested"
    And I proceed as the User
    And I click "Account"
    And I click "Requests For Quote"
    And I click view "PO Test 01" in grid
    And I click "Provide More Information"
    And I type "<script>alert(1)</script>" in "Notes"
    And I click "Submit"
    And I proceed as the Admin
    And I reload the page
    Then I should see "alert(1)"
