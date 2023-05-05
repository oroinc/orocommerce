@regression
@ticket-BB-21440
@fixture-OroWebsiteSearchBundle:search_term_report.yml

Feature: Search terms report

  Scenario: Enable Search History Reporting
    Given I login as administrator
    And I go to System/Configuration
    And follow "Commerce/Search/Search Terms" on configuration sidebar
    When uncheck "Use default" for "Enable Search History Reporting" field
    And I check "Enable Search History Reporting"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check default (by day) report
    When I go to Reports & Segments/Reports/Search/Search Terms
    Then I should see following grid:
      | Time Period | Search Term | NTS | Times returned products | TRER |
      | 10-5-2001   | abba        | 50  | 26                      | 24   |
      | 1-4-2001    | abba        | 100 | 80                      | 20   |
      | 13-4-2000   | boneym      | 40  | 30                      | 10   |

  Scenario: Check report by month
    When I check "Month" in Grouping filter
    Then I should see following grid:
      | Time Period | Search Term | NTS | Times returned products | TRER |
      | 5-2001      | abba        | 50  | 26                      | 24   |
      | 4-2001      | abba        | 100 | 80                      | 20   |
      | 4-2000      | boneym      | 40  | 30                      | 10   |

  Scenario: Check report by quarter
    When I check "Quarter" in Grouping filter
    Then I should see following grid:
      | Time Period | Search Term | NTS | Times returned products | TRER |
      | 2-2001      | abba        | 150 | 106                     | 44   |
      | 2-2000      | boneym      | 40  | 30                      | 10   |

  Scenario: Check report by year
    When I check "Year" in Grouping filter
    Then I should see following grid:
      | Time Period | Search Term | NTS | Times returned products | TRER |
      | 2001        | abba        | 150 | 106                     | 44   |
      | 2000        | boneym      | 40  | 30                      | 10   |

  Scenario: Check sorting
    When sort grid by "Times Returned Products"
    Then I should see following grid:
      | Time Period | Search Term | NTS | Times returned products | TRER |
      | 2000        | boneym      | 40  | 30                      | 10   |
      | 2001        | abba        | 150 | 106                     | 44   |

  Scenario: Check report grid filters
    When I filter "Search term" as contains "bon"
    And I set range filter "Number of Times Searched" as min value "10" and max value "50"
    And I set range filter "Times returned products" as min value "10" and max value "50"
    And I set range filter "Times Returned Empty Result" as min value "1" and max value "20"
    And I filter "Time Period" as between "1999-12-12" and "2015-12-12"
    Then I should see following grid:
      | Time Period | Search Term | NTS | Times returned products | TRER |
      | 2000        | boneym      | 40  | 30                      | 10   |
