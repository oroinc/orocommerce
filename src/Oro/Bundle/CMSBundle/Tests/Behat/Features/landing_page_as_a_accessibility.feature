@regression

Feature: Landing Page as a Accessibility

  Scenario: Feature background
    Given sessions active:
      | Guest | first_session  |
      | Admin | second_session |
    And I set "Accessibility Content" before content for "Accessibility" page

  Scenario: Check default Accessibility setting value
    Given I proceed as the Admin
    And I login as administrator
    When I go to System / Configuration
    And I follow "System Configuration/Websites/Routing" on configuration sidebar
    Then "Routing Settings Form" must contains values:
      | Accessibility | Accessibility |
    And I should see the following options for "Accessibility" select in form "Routing Settings Form":
      | Accessibility |

  Scenario: Check default Accessibility page
    Given I proceed as the Guest
    When I am on homepage
    And I follow the skip to content accessibility link
    Then I should see "Accessibility" in the "Page Title" element
    And I should be on "/accessibility"
    And I should see "Accessibility Content"

  Scenario: Configure localize Accessibility Landing Page title and slug
    Given I proceed as the Admin
    When I go to Marketing / Landing Pages
    And I click edit "Accessibility" in grid
    Then I should see URL Slug field filled with "accessibility"

    When click "Landing Page Form Titles Fallbacks"
    And fill "CMS Page Form" with:
      | Title English (United States) use fallback | false            |
      | Title English (United States) value        | Accessibility-EN |
    And click "Content Node Form Url Slug Fallbacks"
    Then "CMS Page Form" must contains values:
      | URL Slugs English (United States) value | accessibility-en |

    When fill "CMS Page Form" with:
      | URL Slugs English (United States) use fallback | false                   |
      | URL Slugs English (United States) value        | accessibility-en-update |
    And I save form
    Then I should see "Page has been saved" flash message

  Scenario: Check localize Accessibility Landing Page title and slug
    Given I proceed as the Guest
    When I am on homepage
    And I follow the skip to content accessibility link
    Then I should see "Accessibility-EN" in the "Page Title" element
    And I should be on "/accessibility-en-update"

  Scenario: Change Accessibility routing to another Landing Page
    Given I proceed as the Admin
    When I go to System / Configuration
    And I follow "System Configuration/Websites/Routing" on configuration sidebar
    And I fill "Routing Settings Form" with:
      | Accessibility | Cookie Policy |
    And save form
    Then should see "Configuration saved" flash message

  Scenario: Guest sees updated Landing Page after Accessibility configuration change
    Given I proceed as the Guest
    When I am on homepage
    And I follow the skip to content accessibility link
    Then I should not see "Accessibility" in the "Page Title" element
    And I should see "This is the Cookie Policy for OroCommerce application."
