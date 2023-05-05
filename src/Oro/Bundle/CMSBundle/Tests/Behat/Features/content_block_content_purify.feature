@behat-test-env
@waf-skip
@fixture-OroCMSBundle:CustomerUserFixture.yml
@fixture-OroCMSBundle:WysiwygRoleFixture.yml
Feature: Content Block content purify
  In order to restrict access to not available html elements and attributes
  As an Administrator
  I want to purify text data for Content Block form

  Scenario: Edit wysiwyg role
    Given I login as administrator
    And go to System/User Management/Roles
    And I click edit WYSIWYG in grid
    And select following permissions:
      | Content Block | View:Global | Edit:Global | Create:Global |
    When save and close form
    Then I should see "Role saved" flash message

  Scenario: Create user
    Given I go to System/User Management/Users
    And click "Create User"
    And fill form with:
      | Enabled           | Enabled            |
      | First Name        | FName              |
      | Last Name         | LName              |
      | Username          | testUser1@test.com |
      | Password          | testUser1@test.com |
      | Re-Enter Password | testUser1@test.com |
      | Primary Email     | testUser1@test.com |
      | ORO               | true               |
      | Roles             | WYSIWYG            |
    When save and close form
    Then I should see "User saved" flash message

  Scenario: Create new content block with tags in text data in secure mode
    Given go to Marketing / Content Blocks
    And click "Create Content Block"
    And I click "Add Content"
    And fill "Content Block Form" with:
      | Owner           | Main                                          |
      | Alias           | test_alias                                    |
      | Titles          | Test Title                                    |
      | Enabled         | True                                          |
      | Localization    | English                                       |
      | Website         | Default                                       |
      | Customer Group  | Non-Authenticated Visitors                    |
      | Content Variant | Secure content <button>sample button</button> |
    When I save and close form
    Then I should see validation errors:
      | Content Variant | Please remove not permitted HTML-tags in the content field: BUTTON |

    When I fill "Content Block Form" with:
      | Content Variant | xss text <script></script> |
    And I save and close form
    Then I should see validation errors:
      | Content Variant | Please remove not permitted HTML-tags in the content field: SCRIPT |

    When I fill "Content Block Form" with:
      | Content Variant | <div>Some Content</div> |
    And I save and close form
    Then I should see "Content block has been saved" flash message
    And should see Content Block with:
      | Content | Some Content |
    And I should see "Current content view is simplified, please check the page on the Storefront to see the actual result"

  Scenario: Edit Content block in selective mode
    Given login as "testUser1@test.com" user
    And go to Marketing / Content Blocks
    And I click edit "test_alias" in grid

    When I fill "Content Block Form" with:
      | Content Variant | Same text <script></script> Same text |
    And I save and close form
    Then I should see validation errors:
      | Content Variant | Please remove not permitted HTML-tags in the content field: SCRIPT |

    When I fill "Content Block Form" with:
      | Content Variant | Selective content <button>sample button</button> |
    And I save and close form
    Then I should see "Content block has been saved" flash message
    And should see Content Block with:
      | Content | Selective content |
    And I should see "Current content view is simplified, please check the page on the Storefront to see the actual result"
