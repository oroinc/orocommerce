@ticket-BB-4362
@ticket-BAP-18841
@ticket-BAP-20456

Feature: Best Selling Products
  In order to understand what products have been sold best in specific periods of time
  As an Administrator
  I want to have a configurable Best Selling Products report

  Scenario: Best Selling Products report
    Given best selling fixture loaded
    And I login as administrator
    And I have a complete calendar date table from "2016" to "2017"
    And I go to Reports & Segments/ Reports/ Best Selling Products

  Scenario: Sort by time period
    When I sort grid by "Time Period"
    Then there are 15 records in grid
    And I should see following grid:
      | Time Period |
      | 1-1-2016    |
      | 1-1-2016    |
      | 1-1-2016    |
      | 3-1-2016    |
      | 3-1-2016    |
      | 3-1-2016    |
      | 10-1-2016   |
      | 1-2-2016    |
      | 1-2-2016    |
      | 1-5-2016    |
      | 1-5-2016    |
      | 1-6-2016    |
      | 1-6-2016    |
      | 1-1-2017    |
      | 1-1-2017    |
    When I sort grid by "Time Period"
    And I should see following grid:
      | Time Period |
      | 1-1-2017    |
      | 1-1-2017    |
      | 1-6-2016    |
      | 1-6-2016    |
      | 1-5-2016    |
      | 1-5-2016    |
      | 1-2-2016    |
      | 1-2-2016    |
      | 10-1-2016   |
      | 3-1-2016    |
      | 3-1-2016    |
      | 3-1-2016    |
      | 1-1-2016    |
      | 1-1-2016    |
      | 1-1-2016    |

  Scenario: Check grouping filters
    And I sort grid by "Qty Sold"
    Then there are 15 records in grid
    And I should see following grid:
      | Time Period | SKU   | QTY Sold |
      | 1-6-2016    | 9OL25 | 4 Items  |
      | 1-1-2016    | 5GN30 | 5 Items  |
      | 1-1-2017    | 9OL25 | 10 Sets  |
      | 3-1-2016    | 5GN30 | 11 Sets  |
      | 1-1-2016    | 9OL25 | 15 Sets  |
      | 10-1-2016   | 9OL25 | 16 Items |
      | 1-2-2016    | 9OL25 | 17 Sets  |
      | 1-1-2017    | 9OL25 | 20 Items |
      | 1-2-2016    | 9OL25 | 21 Items |
      | 1-1-2016    | 9OL25 | 25 Items |
      | 1-5-2016    | 9OL25 | 30 Items |
      | 3-1-2016    | 9OL25 | 40 Sets  |
      | 3-1-2016    | 9OL25 | 41 Items |
      | 1-5-2016    | 9OL25 | 44 Sets  |
      | 1-6-2016    | 9OL25 | 56 Sets  |
    When I check "Month" in Grouping filter
    Then there are 12 records in grid
    And I should see following grid:
      | Time Period | SKU   | QTY Sold |
      | 6-2016      | 9OL25 | 4 Items  |
      | 1-2016      | 5GN30 | 5 Items  |
      | 1-2017      | 9OL25 | 10 Sets  |
      | 1-2016      | 5GN30 | 11 Sets  |
      | 2-2016      | 9OL25 | 17 Sets  |
      | 1-2017      | 9OL25 | 20 Items |
      | 2-2016      | 9OL25 | 21 Items |
      | 5-2016      | 9OL25 | 30 Items |
      | 5-2016      | 9OL25 | 44 Sets  |
      | 1-2016      | 9OL25 | 55 Sets  |
      | 6-2016      | 9OL25 | 56 Sets  |
      | 1-2016      | 9OL25 | 82 Items |

    When I check "Quarter" in Grouping filter
    Then there are 8 records in grid
    And I should see following grid:
      | Time Period | SKU   | QTY Sold  |
      | 1-2016      | 5GN30 | 5 Items   |
      | 1-2017      | 9OL25 | 10 Sets   |
      | 1-2016      | 5GN30 | 11 Sets   |
      | 1-2017      | 9OL25 | 20 Items  |
      | 2-2016      | 9OL25 | 34 Items  |
      | 1-2016      | 9OL25 | 72 Sets   |
      | 2-2016      | 9OL25 | 100 Sets  |
      | 1-2016      | 9OL25 | 103 Items |

    When I check "Year" in Grouping filter
    Then there are 6 records in grid
    And I should see following grid:
      | Time Period | SKU   | QTY Sold  |
      | 2016        | 5GN30 | 5 Items   |
      | 2017        | 9OL25 | 10 Sets   |
      | 2016        | 5GN30 | 11 Sets   |
      | 2017        | 9OL25 | 20 Items  |
      | 2016        | 9OL25 | 137 Items |
      | 2016        | 9OL25 | 172 Sets  |

    When I filter SKU as Does Not Contain "5GN30"
    Then there are 4 records in grid
    And I should see following grid:
      | Time Period | SKU   | QTY Sold  |
      | 2017        | 9OL25 | 10 Sets   |
      | 2017        | 9OL25 | 20 Items  |
      | 2016        | 9OL25 | 137 Items |
      | 2016        | 9OL25 | 172 Sets  |

    When I check "Day" in Grouping filter
    And I filter Time Period as not between "Jan 1, 2016 12:30 AM" and "Jan 3, 2016 11:30 AM"
    Then there are 9 records in grid
    And I should see following grid:
      | Time Period | SKU   | QTY Sold |
      | 1-6-2016    | 9OL25 | 4 Items  |
      | 1-1-2017    | 9OL25 | 10 Sets  |
      | 10-1-2016   | 9OL25 | 16 Items |
      | 1-2-2016    | 9OL25 | 17 Sets  |
      | 1-1-2017    | 9OL25 | 20 Items |
      | 1-2-2016    | 9OL25 | 21 Items |
      | 1-5-2016    | 9OL25 | 30 Items |
      | 1-5-2016    | 9OL25 | 44 Sets  |
      | 1-6-2016    | 9OL25 | 56 Sets  |

    When I check "Month" in Grouping filter
    And I filter Time Period as between "Jan 1, 2016 11:30 AM" and "Jan 3, 2016 11:30 AM"
    And I check "No" in Skip Empty Periods filter
    And I sort grid by "Qty Sold" again
    Then there are 10 records in grid
    And I should see following grid containing rows:
      | Time Period | SKU   | QTY Sold |
      | 12-2016     |       |          |
      | 10-2016     |       |          |
      | 11-2016     |       |          |
      | 3-2016      |       |          |
      | 4-2016      |       |          |
      | 7-2016      |       |          |
      | 8-2016      |       |          |
      | 9-2016      |       |          |
      | 1-2016      | 9OL25 | 46 Items |
      | 1-2016      | 9OL25 | 40 Sets  |
