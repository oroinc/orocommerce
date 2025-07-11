@fixture-OroSaleBundle:QuoteBackofficeDefaultFixture.yml

Feature: Quote documents
  In order to send quote documents to customer
  As an Administrator
  I should be able to add documents to the quote

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Add files to documents field and check them on view page
    Given I proceed as the Admin
    And I login as administrator
    And I go to Sales/ Quotes
    When I click edit PO11 in grid
    And I click "Add File"
    And I click "Add File"
    And I fill "Quote Customer Documents Form" with:
      | Quote File 1            | file1.txt |
      | Quote File 2            | file2.txt |
      | Quote File Sort Order 1 | 2         |
      | Quote File Sort Order 2 | 1         |
    And I click "Submit"
    Then I should see "Quote #11 successfully updated" flash message
    And I should see following "Quote Customer Documents Grid" grid:
      | Sort order | File name | Uploaded by |
      | 1          | file2.txt | John Doe    |
      | 2          | file1.txt | John Doe    |
    When I click "Send to Customer"
    And I click "Send"
    Then I should see "Quote #11 successfully sent to customer" flash message

  Scenario: View documents on store front
    Given I operate as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I click "Account Dropdown"
    And I click "Quotes"
    When I click view "PO11" in grid
    Then I should see "Documents"
    And I should see "file2.txt"
    And I should see "file1.txt"
