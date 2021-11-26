@regression
@ticket-BB-9578
@ticket-BB-20877
@fixture-OroCustomThemeBundle:collapsible-responsive-filters.yml

Feature: Collapsible responsive filters
  In order for the filters to take less screen space on tables
  As an Frontend Theme Developer
  I want to be able to choose the best way to collapse the filters block in my theme

  # Description
  # Implement additional way to show collapsible/expandable block of filters according to the provided design.
  # Let the theme developer to use this template in his responsive theme.

  # Acceptance Criteria
  # Show how a frontend developer can utilize an alternative collapsible/expandable
  # template for filters in the product listing in his custom theme.

  Scenario: Feature Background
    Given I set configuration property "oro_frontend.frontend_theme" to "custom"
    And sessions active:
      | User | second_session |

  Scenario: Check "Dropdown" filters mode (desktop)
    Given I proceed as the User
    When I am on homepage
    And I click "NewCategory"
    And I should see an "Filter Dropdown Mode" element
    Then I should not see an "Filter Collapse Mode" element

    When I set window size to 992x1024
    Then I should see an "Filter Collapse Mode" element
    And I should not see an "Filter Dropdown Mode" element
    And I click "Filter Collapse Mode"
    And I filter SKU as contains "PSKU1" in "FrontendProductsSearchGrid"
    And I should see "PSKU1"

    When I set window size to 414x736
    Then I should see an "Frontend Grid Action Filter Button" element
    Then I should not see an "Filter Collapse Mode" element
    Then I click "Frontend Grid Action Filter Button"
    Then I should see an "Fullscreen Popup" element
    And I should see "Fullscreen Popup Header" element with text "Filters (1)" inside "Fullscreen Popup" element
    Then I should see "GridFilters" element inside "Fullscreen Popup" element
    And click "Close Fullscreen Popup"

    When I set window size to 992x1024
    Then I should see an "Filter Collapse Mode" element
    And I should not see an "Filter Dropdown Mode" element

    When I set window size to 1440x900
    And I should see an "Filter Dropdown Mode" element
    Then I should not see an "Filter Collapse Mode" element
    And should see filter hints in frontend grid:
      | SKU: contains "PSKU1" |
