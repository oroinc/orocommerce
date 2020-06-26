@regression
@fixture-OroSaleBundle:QuotesGridFrontend.yml
@ticket-BB-13048
@ticket-BAP-17648
@postgresql

Feature: Quotes Grid Frontend Sort Nullables
  In order to ensure frontend quotes grid works correctly
  As a buyer
  I check filters, sorting and columns config are working as designed.

  Scenario: Sort by DNSLT
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I click "Quotes"
    And I sort grid by "DNSLT"
    Then I should see following grid:
      | Quote # |
      | Quote13 |
    When I sort grid by "DNSLT" again
    Then I should see following grid:
      | DNSLT |
      |       |
    And I reset "AllQuotes" grid

  Scenario: Sort by Valid Until
    When I sort grid by "Valid Until"
    Then I should see following grid:
      | Quote # |
      | Quote12 |
    When I sort grid by "Valid Until" again
    Then I should see following grid:
      | Valid Until |
      |             |
    And I reset "AllQuotes" grid

  Scenario: Enable column and Sort by it
    Given I show column "Status" in "AllQuotes" frontend grid
    When I sort grid by "Status"
    Then I should see following grid:
      | Status       |
      | Not Approved |
    When I sort grid by "Status" again
    Then I should see following grid:
      | Status |
      |        |
