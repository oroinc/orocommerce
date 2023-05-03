@regression
@ticket-BB-21440
@fixture-OroProductBundle:product_search/products_with_different_names.yml
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml

Feature: Search history tracking

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Check menu items are not available by default
    Given I proceed as the Admin
    When login as administrator
    Then I should not see the following menu items:
      | Marketing/Search/Search History                |
      | Reports & Segments/Reports/Search/Search Terms |

  Scenario: Check search term are not logged when feature is disabled
    Given I proceed as the User
    And I am on the homepage
    And I type "Flashlight" in "search"
    And I click "Search Button"

    When I proceed as the Admin
    And I go to System/Configuration
    And follow "Commerce/Search/Search Terms" on configuration sidebar
    Then I should not see "Enable Search History Collection"
    When uncheck "Use default" for "Enable Search History Reporting" field
    And I check "Enable Search History Reporting"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message
    And I should see "Enable Search History Collection"
    And I should see the following menu items:
      | Marketing/Search/Search History                |
      | Reports & Segments/Reports/Search/Search Terms |

    When I go to Marketing/Search/Search History
    Then there is no records in grid

  Scenario: Check search terms are saved for guest user when feature is enabled
    When I proceed as the User
    And I type "Flash" in "search"
    And I continue typing "light" in "search"

    And I proceed as the Admin
    And I reload the page
    Then records in grid should be 1
    And I should see following grid containing rows:
      | Search Term | Search result type   | Results    | Website | Localization            | Customer | Customer User |
      | Flashlight  | Product Autocomplete | 2 products | Default | English (United States) |          |               |

  Scenario: Check search terms are saved for logged in user when feature is enabled
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    And I type "Colorful" in "search"
    And I click "Search Button"
    And I type "some_unknown_term" in "search"
    And I click "Search Button"

    When I proceed as the Admin
    And I reload the page
    Then records in grid should be 5
    And I should see following grid containing rows:
      | Search Term       | Search result type   | Results    | Website | Localization            | Customer    | Customer User |
      | Flashlight        | Product Autocomplete | 2 products | Default | English (United States) |             |               |
      | Colorful          | Product Autocomplete | 1 product  | Default | English (United States) | AmandaRCole | Amanda Cole   |
      | some_unknown_term | Empty                |            | Default | English (United States) | AmandaRCole | Amanda Cole   |
      | some_unknown_term | Empty                |            | Default | English (United States) | AmandaRCole | Amanda Cole   |
      | Colorful          | Product Search       | 1 product  | Default | English (United States) | AmandaRCole | Amanda Cole   |

  Scenario: Check Configuration for Organization:
    Given I go to System/User Management/Organizations
    And click "Configuration" on row "ORO" in grid
    And follow "Commerce/Search/Search Terms" on configuration sidebar
    Then I should see "Enable Search History Reporting"
    And I should see "Enable Search History Collection"

  Scenario: Check Configuration for Websites:
    Given I go to System/Websites
    And click "Configuration" on row "Default" in grid
    And follow "Commerce/Search/Search Terms" on configuration sidebar
    Then I should not see "Enable Search History Reporting"
    And I should see "Enable Search History Collection"

  Scenario: Check Configuration for Customer Groups:
    Given I go to Customers/Customer Groups
    And click "Configuration" on row "AmandaRColeGroup" in grid
    And follow "Commerce/Search/Search Terms" on configuration sidebar
    Then I should not see "Enable Search History Reporting"
    And I should see "Enable Search History Collection"

  Scenario: Check search terms are not saved for logged in user when logging is disabled for the Customer
    Given I go to Customers/Customers
    And click "Configuration" on row "AmandaRCole" in grid
    And follow "Commerce/Search/Search Terms" on configuration sidebar
    Then I should not see "Enable Search History Reporting"
    And I should see "Enable Search History Collection"

    When uncheck "Use Customer Group" for "Enable Search History Collection" field
    And I uncheck "Enable Search History Collection"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

    When I proceed as the User
    And I type "Scrub" in "search"
    And I click "Search Button"

    When I proceed as the Admin
    And I go to Marketing/Search/Search History
    Then records in grid should be 5
    And there is no "Scrub" in grid
