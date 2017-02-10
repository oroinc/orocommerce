Feature: Landing Page should have Slug Prototypes which are used for Slugs creation
  As Administrator
  I need to be able to create Landing Page with Slug Prototypes

  Scenario: Create New Landing Page with empty Slug Prototypes
    Given I login as administrator
    When I open Landing Page Create page
      And I fill in Landing Page Titles field with "Test Page"
    Then I should see Slug Prototypes field filled with "test-page"

    When I fill in Slug Prototypes field with ""
      And I click "Save and Close"
    Then I should be on Landing Page View page
    And I should see Landing Page with:
      | Title              | Test Page   |
      | Slugs              | N/A         |

  Scenario: Create New Landing Page with non empty Slug Prototypes
    Given I login as administrator
    When I open Landing Page Create page
    And I fill in Landing Page Titles field with "Test Page"
    Then I should see Slug Prototypes field filled with "test-page"

    When I click "Save and Close"
    And I should see Landing Page with:
      | Title              | Test Page   |
      | Slugs              | /test-page  |
