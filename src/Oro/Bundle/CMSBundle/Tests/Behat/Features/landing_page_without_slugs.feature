@regression
@feature-BB-23050

Feature: Landing Page without Slugs

  Scenario: Create sessions
    Given sessions active:
      | Guest | first_session  |
      | Admin | second_session |

  Scenario: Create Landing Page
    Given I proceed as the Admin
    When I login as administrator
    And I go to Marketing / Landing Pages
    And I click "Create Landing Page"
    Then The "Create A URL Slug" checkbox should be checked
    When I fill in Landing Page Titles field with "Test page"
    Then I should see a "URL Slug Form Field" element
    And I should see URL Slug field filled with "test-page"
    When I uncheck "Create A URL Slug" element
    Then I should not see a "URL Slug Form Field" element
    When I fill in WYSIWYG "CMS Page Content" with "Test page Content"
    And I save and close form
    Then I should see "Page has been saved" flash message

  Scenario: Check availability of the "Test page"
    Given I proceed as the Guest
    When I am on the homepage
    Then Page title equals to "Welcome - Home page"
    And I should not see "Test page Content"
    When I go to "/test-page"
    Then I should see "404 Not Found"
    And Page title equals to "Not Found"
    And I should not see "Test page Content"
