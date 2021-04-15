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

  Scenario: Create different window session
    Given sessions active:
      | Admin          | first_session  |
      | User           | second_session |

  Scenario: Change theme to Custom
    Given I proceed as the Admin
    And login as administrator
    And I go to System/Configuration
    And I follow "Commerce/Design/Theme" on configuration sidebar
    And fill "Theme Form" with:
      | ThemeUseDefault | false        |
      | Theme           | Custom theme |
    Then submit form

  Scenario: Check "Dropdown" filters mode (desktop)
    Given I proceed as the User
    When I am on homepage
    And I click "NewCategory"
    And I should see an "Filter Dropdown Mode" element
    Then I should not see an "Filter Collapse Mode" element

  Scenario: Check "Collapse" filters mode (tablet)
    Given I proceed as the User
    And I set window size to 992x1024
    When I am on homepage
    And I click "Main Menu Button"
    And I click "NewCategoryLink"
    And I should see an "Filter Collapse Mode" element
    And I click "Filter Collapse Mode"
    And I filter SKU as contains "PSKU1" in "FrontendProductsSearchGrid"
    Then I should see "PSKU1"

  Scenario: Check filters in fullscreen popup (mobile)
    Given I proceed as the User
    And I set window size to 414x736
    When I am on homepage
    And I click "Main Menu Button"
    And I click "NewCategoryLink"
    Then I should see an "Frontend Grid Action Filter Button" element
    # Uncomment when behat can be run on mobileEmulation capability
    # And I click "Frontend Grid Action Filter Button"
    # And I should see an "Fullscreen Popup" element
