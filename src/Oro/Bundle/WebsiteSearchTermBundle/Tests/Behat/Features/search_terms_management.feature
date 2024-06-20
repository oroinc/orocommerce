@feature-BB-21439
@fixture-OroWebsiteSearchTermBundle:search_terms.yml

Feature: Search Terms management

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Check Search Terms grid
    Given I proceed as the Admin
    And I login as administrator
    When I go to Marketing / Search / Search Terms
    Then I should see following grid:
      | Phrases                   | Action                                               | Restrictions                                                                                      |
      | search_term6              | Redirect to system page: Welcome - Home page         | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Any                                      |
      | search_term4 search_term5 | Redirect to system page: Welcome - Home page         | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Company A Any Any Any                                |
      | search_term3              | Redirect to: http://non-existing-url.local/someroute | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Localization1 Any Customer Group Any Any |
      | search_term1 search_term2 |                                                      | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Default Any                                  |

  Scenario: Sort by Updated At
    When I sort grid by "Updated At"
    Then I should see following grid:
      | Phrases                   | Action                                               | Restrictions                                                                                      |
      | search_term1 search_term2 |                                                      | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Default Any                                  |
      | search_term3              | Redirect to: http://non-existing-url.local/someroute | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Localization1 Any Customer Group Any Any |
      | search_term4 search_term5 | Redirect to system page: Welcome - Home page         | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Company A Any Any Any                                |
      | search_term6              | Redirect to system page: Welcome - Home page         | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Any                                      |

  Scenario: Sort by Created At
    When I sort grid by "Created At"
    Then I should see following grid:
      | Phrases                   | Action                                               | Restrictions                                                                                      |
      | search_term1 search_term2 |                                                      | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Default Any                                  |
      | search_term3              | Redirect to: http://non-existing-url.local/someroute | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Localization1 Any Customer Group Any Any |
      | search_term4 search_term5 | Redirect to system page: Welcome - Home page         | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Company A Any Any Any                                |
      | search_term6              | Redirect to system page: Welcome - Home page         | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Any                                      |

    When I sort grid by "Created At" again
    Then I should see following grid:
      | Phrases                   | Action                                               | Restrictions                                                                                      |
      | search_term6              | Redirect to system page: Welcome - Home page         | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Any                                      |
      | search_term4 search_term5 | Redirect to system page: Welcome - Home page         | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Company A Any Any Any                                |
      | search_term3              | Redirect to: http://non-existing-url.local/someroute | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Localization1 Any Customer Group Any Any |
      | search_term1 search_term2 |                                                      | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Default Any                                  |

  Scenario: Sort by Partial Match
    Given I show column Partial Match in grid
    When I sort grid by "Partial match"
    Then I should see following grid:
      | Phrases                   | Partial match | Action                                               | Restrictions                                                                                      |
      | search_term3              | No            | Redirect to: http://non-existing-url.local/someroute | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Localization1 Any Customer Group Any Any |
      | search_term4 search_term5 | No            | Redirect to system page: Welcome - Home page         | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Company A Any Any Any                                |
      | search_term6              | No            | Redirect to system page: Welcome - Home page         | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Any                                      |
      | search_term1 search_term2 | Yes           |                                                      | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Default Any                                  |

    When I sort grid by "Partial match" again
    Then I should see following grid:
      | Phrases                   | Partial match | Action                                               | Restrictions                                                                                      |
      | search_term1 search_term2 | Yes           |                                                      | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Default Any                                  |
      | search_term6              | No            | Redirect to system page: Welcome - Home page         | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Any                                      |
      | search_term4 search_term5 | No            | Redirect to system page: Welcome - Home page         | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Company A Any Any Any                                |
      | search_term3              | No            | Redirect to: http://non-existing-url.local/someroute | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Localization1 Any Customer Group Any Any |

  Scenario: Sort by Phrases
    When I sort grid by "Phrases"
    Then I should see following grid:
      | Phrases                   | Action                                               | Restrictions                                                                                      |
      | search_term1 search_term2 |                                                      | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Default Any                                  |
      | search_term3              | Redirect to: http://non-existing-url.local/someroute | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Localization1 Any Customer Group Any Any |
      | search_term4 search_term5 | Redirect to system page: Welcome - Home page         | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Company A Any Any Any                                |
      | search_term6              | Redirect to system page: Welcome - Home page         | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Any                                      |

    When I sort grid by "Phrases" again
    Then I should see following grid:
      | Phrases                   | Action                                               | Restrictions                                                                                      |
      | search_term6              | Redirect to system page: Welcome - Home page         | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Any                                      |
      | search_term4 search_term5 | Redirect to system page: Welcome - Home page         | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Company A Any Any Any                                |
      | search_term3              | Redirect to: http://non-existing-url.local/someroute | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Localization1 Any Customer Group Any Any |
      | search_term1 search_term2 |                                                      | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Default Any                                  |

  Scenario: Filter by Phrases
    When filter Phrases as is equal to "search_term3"
    Then I should see following grid:
      | Phrases      | Action                                               | Restrictions                                                                                      |
      | search_term3 | Redirect to: http://non-existing-url.local/someroute | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Localization1 Any Customer Group Any Any |
    And there is one record in grid

    When filter Phrases as contains "term5"
    Then I should see following grid:
      | Phrases                   | Action                                       | Restrictions                                                       |
      | search_term4 search_term5 | Redirect to system page: Welcome - Home page | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Company A Any Any Any |
    And there is one record in grid

    When filter Phrases as contains "search_term"
    Then I should see following grid:
      | Phrases                   | Action                                               | Restrictions                                                                                      |
      | search_term6              | Redirect to system page: Welcome - Home page         | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Any                                      |
      | search_term4 search_term5 | Redirect to system page: Welcome - Home page         | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Company A Any Any Any                                |
      | search_term3              | Redirect to: http://non-existing-url.local/someroute | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Localization1 Any Customer Group Any Any |
      | search_term1 search_term2 |                                                      | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Default Any                                  |
    And there is 4 records in grid

    When I filter Phrases as Does Not Contain "6"
    Then I should see following grid:
      | Phrases                   | Action                                               | Restrictions                                                                                      |
      | search_term4 search_term5 | Redirect to system page: Welcome - Home page         | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Company A Any Any Any                                |
      | search_term3              | Redirect to: http://non-existing-url.local/someroute | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Localization1 Any Customer Group Any Any |
      | search_term1 search_term2 |                                                      | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Default Any                                  |
    And there is 3 records in grid

  Scenario: Filter by Partial match
    When I reset "Phrases" filter
    And I check "No" in "Partial match: All" filter strictly
    Then I should see following grid:
      | Phrases                   | Action                                               | Restrictions                                                                                      |
      | search_term6              | Redirect to system page: Welcome - Home page         | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Any                                      |
      | search_term4 search_term5 | Redirect to system page: Welcome - Home page         | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Company A Any Any Any                                |
      | search_term3              | Redirect to: http://non-existing-url.local/someroute | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Localization1 Any Customer Group Any Any |
    And there is 3 records in grid

    When I reset "Partial match: No" filter
    And I check "Yes" in "Partial match: All" filter strictly
    Then I should see following grid:
      | Phrases                   | Action | Restrictions                                                     |
      | search_term1 search_term2 |        | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Default Any |
    And there is one record in grid

  Scenario: Filter by Website
    When I reset "Partial match: Yes" filter
    And I check "Default" in Website filter
    Then I should see following grid:
      | Phrases                   | Action | Restrictions                                                     |
      | search_term1 search_term2 |        | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Default Any |
    And there is one record in grid

  Scenario: Filter by Localization
    When I reset "Website" filter
    And I check "Localization1" in Localization filter
    Then I should see following grid:
      | Phrases      | Action                                               | Restrictions                                                                                      |
      | search_term3 | Redirect to: http://non-existing-url.local/someroute | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Localization1 Any Customer Group Any Any |
    And there is one record in grid

  Scenario: Filter by Customer Group
    When I reset "Localization" filter
    And I check "Customer Group" in Customer Group filter
    Then I should see following grid:
      | Phrases      | Action                                               | Restrictions                                                                                      |
      | search_term3 | Redirect to: http://non-existing-url.local/someroute | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Localization1 Any Customer Group Any Any |
    And there is one record in grid

  Scenario: Filter by Customers
    When I reset "Customer Group" filter
    And I check "Company A" in Customer filter
    Then I should see following grid:
      | Phrases                   | Action                                       | Restrictions                                                       |
      | search_term4 search_term5 | Redirect to system page: Welcome - Home page | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Company A Any Any Any |
    And there is one record in grid

  Scenario: Remove one record
    When I reset "Customer" filter
    And I click delete "search_term3" in grid
    Then I should see "Are you sure you want to delete this item?"
    When I click "Yes, Delete"
    Then I should see "Search Term has been deleted" flash message
    And I should see following grid:
      | Phrases                   | Action                                       | Restrictions                                                       |
      | search_term6              | Redirect to system page: Welcome - Home page | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Any Any       |
      | search_term4 search_term5 | Redirect to system page: Welcome - Home page | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Company A Any Any Any |
      | search_term1 search_term2 |                                              | CUSTOMER CUSTOMER GROUP WEBSITE LOCALIZATION Any Any Default Any   |
    And there is 3 records in grid

  Scenario: Select and delete all records
    When I check all records in grid
    And I click "Delete" link from mass action dropdown
    And confirm deletion
    Then there is no records in grid
