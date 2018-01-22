@ticket-BB-13048

Feature: Quote frontend grid
  In order to view quote on front store
  As a Buyer
  I want to have ability to see my quotes on grid

  Scenario: Check pagination
    Given sent to customer quotes fixture loaded
    And I login as AmandaRCole@example.org buyer
    When I click "Quotes"
    And number of records in "AllQuotes" should be 15
    And I select 10 records per page in "AllQuotes"
    Then I should see following records in "AllQuotes":
      | PO1  |
      | PO2  |
      | PO3  |
      | PO4  |
      | PO5  |
      | PO6  |
      | PO7  |
      | PO8  |
      | PO9  |
      | PO10 |

    When I go to next page in grid
    Then I should see following records in "AllQuotes":
      | PO11 |
      | PO12 |
      | PO13 |
      | PO14 |
      | PO15 |
