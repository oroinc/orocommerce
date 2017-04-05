Feature: URL Slug for Landing Page
  In order to be sure that every landing page is accessible for it's own url
  As administrator
  I need to be able to create Landing Page with URL Slug

  Scenario: Create New Landing Page with empty URL Slug
    Given I login as administrator
    When I open Landing Page Create page
    And I fill in Landing Page Titles field with "Test Page"
    Then I should see URL Slug field filled with "test-page"

    When I fill in URL Slug field with ""
    And I save and close form
    Then I should be on Landing Page View page
    And I should see Landing Page with:
      | Title              | Test Page   |
      | Slugs              | N/A         |

  Scenario: Create New Landing Page with non empty URL Slug
    When I open Landing Page Create page
    And I fill in Landing Page Titles field with "Other Page"
    Then I should see URL Slug field filled with "other-page"
    When I save and close form
    And reload the page
    And I should see Landing Page with:
      | Title              | Other Page   |
      | Slugs              | [/other-page]  |

  Scenario: Delete pages
    Given I go to Marketing/ Landing Pages
    And check all records in grid
    When I click Delete mass action
    And confirm deletion
    Then I should see success message with number of records were deleted
