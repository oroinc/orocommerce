Feature: URL Slug for Landing Page
  In order to be sure that every landing page is accessible for it's own url
  As administrator
  I need to be able to create Landing Page with URL Slug

  Scenario: Create New Landing Page with non empty URL Slug
    Given I login as administrator
    And I open Landing Page Create page
    When I fill in Landing Page Titles field with "Other Page"
    Then I should see URL Slug field filled with "other-page"
    When I save and close form
    And reload the page
    Then I should see Landing Page with:
      | Title | Other Page    |
      | Slugs | [/other-page] |

  Scenario: Landing page with an automatically generated URL Slug
    Given I click "Edit"
    When I fill in Landing Page Titles field with "Other Page Acme"
    And fill in URL Slug field with ""
    And save and close form
    And click "Apply" in modal window
    And reload the page
    Then I should see Landing Page with:
      | Title | Other Page Acme    |
      | Slugs | [/other-page-acme] |

  Scenario: Create New Landing Page with non empty URL Slug
    Given I open Landing Page Create page
    When I type "Immediately saved page" in Landing Page Titles field
    And I save and close form
    And reload the page
    Then I should see Landing Page with:
      | Title | Immediately saved page    |
      | Slugs | [/immediately-saved-page] |

  Scenario: Delete pages
    Given I go to Marketing/ Landing Pages
    And check all records in grid
    When I click Delete mass action
    And confirm deletion
    Then I should see success message with number of records were deleted
