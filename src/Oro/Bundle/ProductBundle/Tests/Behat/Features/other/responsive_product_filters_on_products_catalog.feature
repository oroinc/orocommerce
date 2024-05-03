@regression
@ticket-BB-20877
@fixture-OroProductBundle:product_frontend.yml

Feature: Responsive Product Filters On Products Catalog
  In order to simplify applying filters on tablet devices with big screens
  As a Frontend Theme Developer
  I want to provide a dedicated popup to work with filters

  Scenario: Feature Background
    Given I set configuration property "oro_product.filters_display_settings_state" to "expanded"
    And sessions active:
      | admin    | first_session  |
      | customer | second_session |

  Scenario: Check Filter Panel State
    Given I proceed as the customer
    And I login as AmandaRCole@example.org buyer
    When I click "NewCategory" in hamburger menu
    Then I should see an "GridFilters" element

    When I set window size to 992x1024
    Then I should not see an "GridFilters" element
    And I click "GridFiltersButton"
    Then I should see an "Fullscreen Popup" element
    And I should see "Fullscreen Popup Header" element with text "Filter Toggle" inside "Fullscreen Popup" element
    And click "Close Fullscreen Popup"

    When I set window size to 1440x900
    Then I should see an "GridFilters" element
    And I click "GridFiltersButton"
    Then I should not see an "Fullscreen Popup" element
    Then I should not see an "GridFilters" element
    And I click "GridFiltersButton"
    Then I should see an "GridFilters" element

  Scenario: Responsive transformation from dropdown to fullscreen views
    Given filter SKU as is equal to "SKU1"
    And I should see "SKU1" product

    When I set window size to 992x1024
    And I click "GridFiltersButton"
    Then I should see an "Fullscreen Popup" element
    And I should see "Fullscreen Popup Header" element with text "Filters (1)" inside "Fullscreen Popup" element
    Then I should see "GridFilters" element inside "Fullscreen Popup" element
    Then I should see "FrontendGridFilterManagerButton" element inside "Fullscreen Popup" element
    And click "Close Fullscreen Popup"
    When click "GridFiltersState"
    Then I should see an "Fullscreen Popup" element
    And click "Close Fullscreen Popup"

    When I set window size to 1440x900
    Then should see filter hints in frontend grid:
      | SKU: is equal to "SKU1" |

  Scenario: Responsive transformation from dropdown to fullscreen views if filters in sidebar
    Given I proceed as the admin
    And I login as administrator
    And I go to System / Theme Configurations
    When I click "Edit" on row "Refreshing Teal" in grid
    And I fill "Theme Configuration Form" with:
      | Filter Panel Position | sidebar |
    And I save and close form
    Then I should see "Theme Configuration has been saved" flash message

    When I proceed as the customer
    And I reload the page
    And I set filter Any Text as contains "product"
    And I click "Apply"
    Then I should see filter hints in frontend grid:
      | Any Text: contains "product" |

    When I set window size to 992x1024
    And I click "GridFiltersButton"
    Then I should see an "Fullscreen Popup" element
    And I should see "Fullscreen Popup Header" element with text "Filters (2)" inside "Fullscreen Popup" element
    Then I should see "GridFilters" element inside "Fullscreen Popup" element
    Then I should see "FrontendGridFilterManagerButton" element inside "Fullscreen Popup" element
    And click "Close Fullscreen Popup"
    When click "GridFiltersState"
    Then I should see an "Fullscreen Popup" element
    And click "Close Fullscreen Popup"

    When I set window size to 1440x900
    And I scroll to top
    Then should see filter hints in frontend grid:
      | Any Text: contains "product" |
