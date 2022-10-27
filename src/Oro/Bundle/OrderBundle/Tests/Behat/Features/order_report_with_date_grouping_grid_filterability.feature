@ticket-BAP-20456

Feature: Order report with date grouping grid filterability
  In order to build custom report for order entity grouped by date
  As an admin
  I should have the possibility to create a report and be able to use any available filter in the resulting report grid

  Scenario: Create report without aggregation filter
    Given best selling fixture loaded
    When I login as administrator
    And I have a complete calendar date table from "2016" to "2017"
    And I go to Reports & Segments/ Manage Custom Reports
    And I click "Create Report"
    And I fill "Report Form" with:
      | Name        | Order by date |
      | Entity      | Order         |
      | Report Type | Table         |
    And I add the following columns:
      | Total ($)       | Sum  |
      | Order Number    | None |
      | Internal Status | None |
    And I add the following grouping columns:
      | Order Number    |
      | Internal Status |
    And I fill form with:
      | Enable grouping by date         | true |
      | Allow To Skip Empty Time Period | true |
    And I select "Created At" from date grouping field
    And I save and close form
    Then I should see "Report saved" flash message

  Scenario: Check grid filters when there is no aggregation filter in the report
    Given there are 16 records in grid
    When I sort grid by "Total ($)"
    Then I should see following grid:
      | Time Period | Total ($) | Order Number | Internal Status |
      | 1-1-2016    | 10        | OID1         | Open            |
      | 1-1-2016    | 20        | OID2         | Open            |
      | 1-1-2016    | 30        | OID3         | Open            |
      | 1-1-2016    | 40        | OID4         | Open            |
      | 10-1-2016   | 50        | OID5         | Open            |
      | 3-1-2016    | 60        | OID6         | Open            |
      | 3-1-2016    | 70        | OID7         | Open            |
      | 3-1-2016    | 80        | OID8         | Open            |
      | 1-2-2016    | 90        | OID9         | Open            |
      | 1-2-2016    | 100       | OID10        | Open            |
      | 1-5-2016    | 110       | OID11        | Open            |
      | 1-5-2016    | 120       | OID12        | Archived        |
      | 1-6-2016    | 130       | OID13        | Cancelled       |
      | 1-6-2016    | 140       | OID14        | Closed          |
      | 1-1-2017    | 150       | OID15        | Open            |
      | 1-1-2017    | 160       | OID16        | Shipped         |

    When I filter Time Period as between "Feb 1, 2016 12:00 AM" and "Jan 1, 2018 12:00 AM"
    Then there are 8 records in grid
    And I should see following grid:
      | Time Period | Total ($) | Order Number | Internal Status |
      | 1-2-2016    | 90        | OID9         | Open            |
      | 1-2-2016    | 100       | OID10        | Open            |
      | 1-5-2016    | 110       | OID11        | Open            |
      | 1-5-2016    | 120       | OID12        | Archived        |
      | 1-6-2016    | 130       | OID13        | Cancelled       |
      | 1-6-2016    | 140       | OID14        | Closed          |
      | 1-1-2017    | 150       | OID15        | Open            |
      | 1-1-2017    | 160       | OID16        | Shipped         |

    When I choose filter for Internal Status as Is Any Of "Open"
    Then there are 4 records in grid
    And I should see following grid:
      | Time Period | Total ($) | Order Number | Internal Status |
      | 1-2-2016    | 90        | OID9         | Open            |
      | 1-2-2016    | 100       | OID10        | Open            |
      | 1-5-2016    | 110       | OID11        | Open            |
      | 1-1-2017    | 150       | OID15        | Open            |

    When I filter "Total ($)" as more than "99"
    Then there are 3 records in grid
    And I should see following grid:
      | Time Period | Total ($) | Order Number | Internal Status |
      | 1-2-2016    | 100       | OID10        | Open            |
      | 1-5-2016    | 110       | OID11        | Open            |
      | 1-1-2017    | 150       | OID15        | Open            |

  Scenario: Add aggregation filter to the report
    Given I click "Edit"
    And add the following filters:
      | Aggregation column | Total ($) | greater than | 1.0 |
    And I save and close form
    Then I should see "Report saved" flash message

  Scenario: Check grid filters when there is aggregation filter in the report
    Given there are 16 records in grid
    When I sort grid by "Total ($)"
    Then I should see following grid:
      | Time Period | Total ($) | Order Number | Internal Status |
      | 1-1-2016    | 10        | OID1         | Open            |
      | 1-1-2016    | 20        | OID2         | Open            |
      | 1-1-2016    | 30        | OID3         | Open            |
      | 1-1-2016    | 40        | OID4         | Open            |
      | 10-1-2016   | 50        | OID5         | Open            |
      | 3-1-2016    | 60        | OID6         | Open            |
      | 3-1-2016    | 70        | OID7         | Open            |
      | 3-1-2016    | 80        | OID8         | Open            |
      | 1-2-2016    | 90        | OID9         | Open            |
      | 1-2-2016    | 100       | OID10        | Open            |
      | 1-5-2016    | 110       | OID11        | Open            |
      | 1-5-2016    | 120       | OID12        | Archived        |
      | 1-6-2016    | 130       | OID13        | Cancelled       |
      | 1-6-2016    | 140       | OID14        | Closed          |
      | 1-1-2017    | 150       | OID15        | Open            |
      | 1-1-2017    | 160       | OID16        | Shipped         |

    When I filter Time Period as between "Feb 1, 2016 12:00 AM" and "Jan 1, 2018 12:00 AM"
    Then there are 8 records in grid
    And I should see following grid:
      | Time Period | Total ($) | Order Number | Internal Status |
      | 1-2-2016    | 90        | OID9         | Open            |
      | 1-2-2016    | 100       | OID10        | Open            |
      | 1-5-2016    | 110       | OID11        | Open            |
      | 1-5-2016    | 120       | OID12        | Archived        |
      | 1-6-2016    | 130       | OID13        | Cancelled       |
      | 1-6-2016    | 140       | OID14        | Closed          |
      | 1-1-2017    | 150       | OID15        | Open            |
      | 1-1-2017    | 160       | OID16        | Shipped         |

    When I choose filter for Internal Status as Is Any Of "Open"
    Then there are 4 records in grid
    And I should see following grid:
      | Time Period | Total ($) | Order Number | Internal Status |
      | 1-2-2016    | 90        | OID9         | Open            |
      | 1-2-2016    | 100       | OID10        | Open            |
      | 1-5-2016    | 110       | OID11        | Open            |
      | 1-1-2017    | 150       | OID15        | Open            |

    When I filter "Total ($)" as more than "99"
    Then there are 3 records in grid
    And I should see following grid:
      | Time Period | Total ($) | Order Number | Internal Status |
      | 1-2-2016    | 100       | OID10        | Open            |
      | 1-5-2016    | 110       | OID11        | Open            |
      | 1-1-2017    | 150       | OID15        | Open            |
