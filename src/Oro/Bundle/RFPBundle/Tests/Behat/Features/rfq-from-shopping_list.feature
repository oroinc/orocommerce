@fixture-OroShoppingListBundle:ShoppingListFixture.yml
@ticket-BB-9841
Feature: RFQ from Shipping List

Scenario: Create RFQ from shopping list by Marlene
    Given I signed in as MarleneSBradley@example.com on the store frontend
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
