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
      | Owner           | Main                                                                                    |
      | Alias           | test_alias                                                                              |
      | Titles          | Test Title                                                                              |
      | Enabled         | True                                                                                    |
      | Localization    | English                                                                                 |
      | Website         | Default                                                                                 |
      | Customer Group  | Non-Authenticated Visitors                                                              |
      | Content Variant | Secure content <iframe src=\"https://www.youtube.com/embed/\" allowfullscreen></iframe> |
    When I save and close form
    Then I should see only "The entered content is not permitted in this field. Please remove the potentially unsecure elements, or contact the system administrators to lift the restrictions." error message

    When I fill "Content Block Form" with:
      | Content Variant | xss text <script></script> |
    And I save and close form
    Then I should see only "The entered content is not permitted in this field. Please remove the potentially unsecure elements, or contact the system administrators to lift the restrictions." error message

    When I fill "Content Block Form" with:
      | Content Variant | <div>Some Content</div> |
    And I save and close form
    Then I should see "Content block has been saved" flash message
    And should see Content Block with:
      | Content | <div>Some Content</div> |

  Scenario: Edit Content block in selective mode
    Given login as "testUser1@test.com" user
    And go to Marketing / Content Blocks
    And I click edit "test_alias" in grid

    When I fill "Content Block Form" with:
      | Content Variant | Same text <script></script> Same text |
    And I save and close form
    Then I should see only "The entered content is not permitted in this field. Please remove the potentially unsecure elements, or contact the system administrators to lift the restrictions." error message

    When I fill "Content Block Form" with:
      | Content Variant | Selective content <iframe src=\"https://www.youtube.com/embed/\" allowfullscreen></iframe> |
    And I save and close form
    Then I should see "Content block has been saved" flash message
    And should see Content Block with:
      | Content | Selective content |
