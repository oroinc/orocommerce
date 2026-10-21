@regression
@ticket-BB-28044

Feature: Pinned search terms page when search history menu item is placed above it
  In order to navigate between pinned pages
  As an administrator
  I want the Search Terms page to keep its own title regardless of the Search History menu item position

  Scenario: Enable search history reporting
    Given I login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Search/Search Terms" on configuration sidebar
    When uncheck "Use default" for "Enable Search History Reporting" field
    And I check "Enable Search History Reporting"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message
    And I should see the following menu items:
      | Marketing/Search/Search History |
      | Marketing/Search/Search Terms   |

  Scenario: Move Search History menu item above Search Terms
    Given I go to System/Menus
    And I click view application_menu in grid
    And I expand "Marketing" in tree "Sidebar Menu Tree"
    And I expand "Search" in tree "Sidebar Menu Tree"
    When I move "Search History" before "Search Terms" in tree "Sidebar Menu Tree"
    And I reload the page
    Then I should see "Search Terms" after "Search History" in tree "Sidebar Menu Tree"

  Scenario: Pin Search Terms page
    Given I go to Marketing/Search/Search Terms
    Then I should see "Marketing/ Search/ Search Terms" in breadcrumbs
    When I pin page
    Then Search Terms link must be in pin holder
    And Search History link must not be in pin holder

  Scenario: Pin Search History page
    Given I go to Marketing/Search/Search History
    Then I should see "Marketing/ Search/ Search History" in breadcrumbs
    When I pin page
    Then Search History link must be in pin holder
