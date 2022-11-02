@regression
@fixture-OroSaleBundle:QuotesGrid.yml
@ticket-BAP-17648
@postgresql

Feature: Quotes Grid Sorting nullables
  In order to ensure backoffice All Quotes Grid works correctly
  As an administrator
  I check filters are working, sorting is working and columns config is working as designed.

  Scenario: Check Quote qid filter
    Given I login as administrator
    When I go to Sales / Quotes
    Then I should see following grid:
      | Quote # |
      | Quote1  |
      | Quote10 |
    When I sort grid by "Customer Status"
    Then I should see following grid:
      | Quote # |
      | Quote10 |
      | Quote1  |
    When I sort grid by "Customer Status" again
    Then I should see following grid:
      | Quote # |
      | Quote20 |
      | Quote19 |
    And I reset "All Quotes Grid" grid

  Scenario: Sort by Valid Until
    Given I should see following grid:
      | Quote # |
      | Quote1  |
      | Quote10 |
    When I sort grid by "Valid Until"
    Then I should see following grid:
      | Quote # |
      | Quote12 |
      | Quote1  |
    When I sort grid by "Valid Until" again
    Then I should see following grid:
      | Quote # |
      | Quote20 |
      | Quote19 |
    And I reset "All Quotes Grid" grid

  Scenario: Sort by DNSLT
    Given I should see following grid:
      | Quote # |
      | Quote1  |
      | Quote10 |
    When I sort grid by "DNSLT"
    Then I should see following grid:
      | Quote # |
      | Quote13 |
      | Quote1  |
    When I sort grid by "DNSLT" again
    Then I should see following grid:
      | Quote # |
      | Quote20 |
      | Quote19 |
    And I reset "All Quotes Grid" grid

  Scenario: Enable column "Payment Term" and Sort by it
    Given I should see following grid:
      | Quote # |
      | Quote1  |
      | Quote10 |
    And I show column Payment Term in grid
    When I sort grid by "Payment Term"
    Then I should see following grid:
      | Quote # |
      | Quote14 |
      | Quote1  |
    When I sort grid by "Payment Term" again
    Then I should see following grid:
      | Quote # |
      | Quote20 |
      | Quote19 |
    And I reset "All Quotes Grid" grid
