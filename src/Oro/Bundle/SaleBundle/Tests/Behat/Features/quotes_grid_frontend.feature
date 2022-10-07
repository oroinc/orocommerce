@regression
@fixture-OroSaleBundle:QuotesGridFrontend.yml
@ticket-BB-13048
@ticket-BAP-17648

Feature: Quotes Grid Frontend
  In order to ensure frontend quotes grid works correctly
  As a buyer
  I check filters, sorting and columns config are working as designed.

  Scenario: Check Grid
    Given I signed in as AmandaRCole@example.org on the store frontend
    When I click "Quotes"
    And number of records in "AllQuotes" should be 13
    And I select 10 records per page in "AllQuotes"
    Then I should see following records in "AllQuotes":
      | Quote1  |
      | Quote2  |
      | Quote3  |
      | Quote4  |
      | Quote5  |
      | Quote6  |
      | Quote7  |
      | Quote8  |
      | Quote9  |
      | Quote10 |
    When I go to next page in "AllQuotes"
    Then I should see following records in "AllQuotes":
      | Quote11 |
      | Quote12 |
      | Quote13 |
    And I reset "AllQuotes" grid

  Scenario: Check Quote qid filter
    Given number of records in "AllQuotes" should be 13
    When I filter "Quote #" as contains "Quote2"
    Then I should see following grid:
      | Quote # |
      | Quote2  |
    And number of records in "AllQuotes" should be 1
    And I reset "AllQuotes" grid

  Scenario: Check Quote grid filter on tablet view
    Given I set window size to 992x1024
    And I click "GridFiltersButton"
    Then I should see an "Fullscreen Popup" element
    And I set filter Quote as is empty
    And I should see "Fullscreen Popup Apply Filters" button enabled
    And I set filter Quote as is not empty
    And I should see "Fullscreen Popup Apply Filters" button enabled
    When I click "Fullscreen Popup Apply Filters"
    Then I should not see an "Fullscreen Popup" element
    And I reset "AllQuotes" grid

  Scenario: Check PO Number filter
    Given number of records in "AllQuotes" should be 13
    When I filter PO Number as contains "PO10"
    Then I should see following grid:
      | Quote # |
      | Quote10 |
    And number of records in "AllQuotes" should be 1
    And I reset "AllQuotes" grid

  Scenario: Check Ship Until filter
    Given number of records in "AllQuotes" should be 13
    When I filter Do Not Ship Later Than as between "today-2" and "today-1"
    Then there are no records in grid
    When I filter Do Not Ship Later Than as between "today" and "today+1"
    Then I should see following grid:
      | Quote # |
      | Quote13 |
    And number of records in "AllQuotes" should be 1
    And I reset "AllQuotes" grid

  Scenario: Check Valid Until filter
    Given number of records in "AllQuotes" should be 13
    When I filter Valid Until as between "today-2" and "today-1"
    Then there are no records in grid
    When I filter Valid Until as between "today" and "today+1"
    Then I should see following grid:
      | Quote # |
      | Quote12 |
    And number of records in "AllQuotes" should be 1
    And I reset "AllQuotes" grid

  Scenario: Check Created At filter
    Given number of records in "AllQuotes" should be 13
    When I filter Created At as between "today-2" and "today-1"
    Then there are no records in grid
    When I filter Created At as between "today" and "today+1"
    Then number of records in "AllQuotes" should be 13
    And I reset "AllQuotes" grid

  Scenario: Enable & Check Status filter
    Given number of records in "AllQuotes" should be 13
    And I show filter "Status" in "AllQuotes" frontend grid
    When I choose filter for Status as is any of "Not Approved"
    Then I should see following grid:
      | Quote # |
      | Quote10 |
    And number of records in "AllQuotes" should be 1
    And I reset "AllQuotes" grid

  Scenario: Check Owner filter
    Given number of records in "AllQuotes" should be 13
    When I filter Owner as contains "nancy"
    Then I should see following grid:
      | Quote # |
      | Quote7  |
    And number of records in "AllQuotes" should be 1

  Scenario: Check Filter Applies After Different Actions
    Given I hide column Owner in "AllQuotes"
    Then I should see following grid:
      | Quote # |
      | Quote7  |
    And number of records in "AllQuotes" should be 1
    When I filter Owner as contains "amanda"
    Then I should not see "Quote7"
    And number of records in "AllQuotes" should be 12
    When I select 10 from per page list dropdown in "AllQuotes"
    Then records in grid should be 10
    And I should not see "Quote7"
    When I go to next page in "AllQuotes"
    Then records in grid should be 2
    When I refresh "AllQuotes" grid
    Then records in grid should be 2
    When I reload the page
    Then records in grid should be 2
    When I hide filter "Owner" in "AllQuotes" frontend grid
    Then number of records in "AllQuotes" should be 13
    When I reset "AllQuotes" grid
    Then number of records in "AllQuotes" should be 13
    And records in grid should be 13

  Scenario: Sort by Quote #
    When I sort grid by "Quote #"
    Then I should see following grid:
      | Quote # |
      | Quote1  |
      | Quote10 |
    When I sort grid by "Quote #" again
    Then I should see following grid:
      | Quote # |
      | Quote9  |
      | Quote8  |
    And I reset "AllQuotes" grid

  Scenario: Sort by PO Number
    When I sort grid by "PO Number"
    Then I should see following grid:
      | PO Number |
      | PO1       |
      | PO10      |
    When I sort grid by "PO Number" again
    Then I should see following grid:
      | PO Number |
      | PO9       |
      | PO8       |
    And I reset "AllQuotes" grid

      | Quote # |
      | Quote9  |
      | Quote8  |
    And I reset "AllQuotes" grid

  Scenario: Sort by Created At
    Given I should see following grid:
      | Quote # |
      | Quote1  |
      | Quote2  |
    When I sort grid by "Created At"
    Then I should see following grid:
      | Quote # |
      | Quote1  |
      | Quote2  |
    When I sort grid by "Created At" again
    Then I should see following grid:
      | Quote # |
      | Quote13 |
      | Quote12 |

  Scenario: Sort by Owner
    When I sort grid by "Owner"
    Then I should see following grid:
      | Owner       |
      | Amanda Cole |
      | Amanda Cole |
    When I sort grid by "Owner" again
    Then I should see following grid:
      | Owner        |
      | Nancy Sallee |
      | Amanda Cole  |
    And I reset "AllQuotes" grid

  Scenario: Check Sorter Applies After Different Actions
    Given I show column "Status" in "AllQuotes" frontend grid
    And I sort grid by "Quote #"
    And I sort grid by "Quote #" again
    Given I hide column "Status" in "AllQuotes" frontend grid
    Then I should see following grid:
      | Quote # |
      | Quote9  |
      | Quote8  |
    When I select 10 from per page list dropdown in "AllQuotes"
    Then records in grid should be 10
    And I should see following grid:
      | Quote # |
      | Quote9  |
      | Quote8  |
    When I go to next page in "AllQuotes"
    Then I should see following grid:
      | Quote # |
      | Quote11 |
      | Quote10 |
    When I reload the page
    Then I should see following grid:
      | Quote # |
      | Quote11 |
      | Quote10 |
    When I reset "AllQuotes" grid
    Then number of records in "AllQuotes" should be 13
    And records in grid should be 13

  Scenario: Check columns are loaded correctly
    Given I hide all columns in "AllQuotes" frontend grid except Quote #
    When I show column "Owner" in "AllQuotes" frontend grid
    Then I should see "Owner" column in "AllQuotes" frontend grid
    And I should see following grid with exact columns order:
      | Quote # | Owner       |
      | Quote1  | Amanda Cole |
    When I show column "PO Number" in "AllQuotes" frontend grid
    Then I should see "PO Number" column in "AllQuotes" frontend grid
    And I should see following grid with exact columns order:
      | Quote # | PO Number | Owner       |
      | Quote1  | PO1       | Amanda Cole |

  Scenario: Check Columns Config Applies After Different Actions
    Given number of records in "AllQuotes" should be 13
    When I select 10 from per page list dropdown in "AllQuotes"
    Then records in grid should be 10
    And I should see following grid with exact columns order:
      | Quote # | PO Number | Owner        |
      | Quote1  | PO1       | Amanda Cole  |
      | Quote2  | PO2       | Amanda Cole  |
      | Quote3  | PO3       | Amanda Cole  |
      | Quote4  | PO4       | Amanda Cole  |
      | Quote5  | PO5       | Amanda Cole  |
      | Quote6  | PO6       | Amanda Cole  |
      | Quote7  | PO7       | Nancy Sallee |
      | Quote8  | PO8       | Amanda Cole  |
      | Quote9  | PO9       | Amanda Cole  |
      | Quote10 | PO10      | Amanda Cole  |
    When I go to next page in "AllQuotes"
    And I should see following grid with exact columns order:
      | Quote # | PO Number | Owner       |
      | Quote11 | PO11      | Amanda Cole |
      | Quote12 | PO12      | Amanda Cole |
      | Quote13 | PO13      | Amanda Cole |
    When I reload the page
    And I should see following grid with exact columns order:
      | Quote # | PO Number | Owner       |
      | Quote11 | PO11      | Amanda Cole |
      | Quote12 | PO12      | Amanda Cole |
      | Quote13 | PO13      | Amanda Cole |
    When I reset "AllQuotes" grid
    Then I should see following grid with exact columns order:
      | Quote # | PO Number |
      | Quote1  | PO1       |
      | Quote2  | PO2       |
      | Quote3  | PO3       |
      | Quote4  | PO4       |
      | Quote5  | PO5       |
      | Quote6  | PO6       |
      | Quote7  | PO7       |
      | Quote8  | PO8       |
      | Quote9  | PO9       |
      | Quote10 | PO10      |
      | Quote11 | PO11      |
      | Quote12 | PO12      |
      | Quote13 | PO13      |

  Scenario: Check Grid View is Saved and Restored Properly
    Given I hide all columns in "AllQuotes" frontend grid except Quote #
    And I show column "Owner" in "AllQuotes" frontend grid
    And I sort grid by "Owner"
    And I filter "Quote #" as contains "Quote6"
    When I click grid view list on "AllQuotes" grid
    And I click "Save As New"
    And I set "gridview1" as grid view name for "AllQuotes" grid on frontend
    And I click "Add"
    Then I should see "View has been successfully created" flash message
    When I reset "AllQuotes" grid
    Then I should see following grid with exact columns order:
      | Quote # | PO Number | DNSLT |
      | Quote1  | PO1       |       |
    And I reload the page
    When I switch to "gridview1" grid view in "AllQuotes" frontend grid
    Then I should see following grid with exact columns order:
      | Quote # | Owner       |
      | Quote6  | Amanda Cole |
    When I set "gridview1" grid view as default in "AllQuotes" frontend grid
    Then I should see "View has been successfully updated" flash message
    When I click "Quotes"
    Then I should see "Gridview1"
    And I should see following grid with exact columns order:
      | Quote # | Owner       |
      | Quote6  | Amanda Cole |
    When I delete "gridview1" grid view in "AllQuotes" frontend grid
    And I confirm deletion
    Then I should see "View has been successfully deleted" flash message
    And I should see following grid with exact columns order:
      | Quote # | PO Number |
      | Quote1  | PO1       |
      | Quote2  | PO2       |
      | Quote3  | PO3       |
      | Quote4  | PO4       |
      | Quote5  | PO5       |
      | Quote6  | PO6       |
      | Quote7  | PO7       |
      | Quote8  | PO8       |
      | Quote9  | PO9       |
      | Quote10 | PO10      |
      | Quote11 | PO11      |
      | Quote12 | PO12      |
      | Quote13 | PO13      |
