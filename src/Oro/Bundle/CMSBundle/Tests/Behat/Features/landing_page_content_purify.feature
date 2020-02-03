@feature-BB-17656
@ticket-BB-18771
@fixture-OroCMSBundle:WysiwygRoleFixture.yml
Feature: Landing Page content purify
  In order to restrict access to attributes that may be vulnerable
  As an Administrator
  I want to purify text data for Landing Page form

  Scenario: Edit wysiwyg role
    Given I login as administrator
    And go to System/User Management/Roles
    And I click edit WYSIWYG in grid
    And select following permissions:
      | Landing Page | View:Global | Edit:Global | Create:Global |
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

  Scenario: Create a new Landing Page with not allowed elements in content text in secure mode
    Given I go to Marketing / Landing Pages
    And click "Create Landing Page"
    And I fill "CMS Page Form" with:
      | Titles  | Other page                                                                              |
      | Content | Secure content <iframe src=\"https://www.youtube.com/embed/\" allowfullscreen></iframe> |
    And I save and close form
    Then I should see "Please remove not permitted HTML-tags in the content field: Iframe" error message
    When I fill "CMS Page Form" with:
      | Content | <div>Some Content</div> |
    And I save and close form
    Then I should see "Page has been saved" flash message
    And should see "Some Content"
    And I should see "Current content view is simplified, please check the page on the Storefront to see the actual result"

  Scenario: Edit Landing Page in selective mode
    Given login as "testUser1@test.com" user
    And go to Marketing / Landing Pages
    And I click edit Other page in grid
    And I fill "CMS Page Form" with:
      | Content | Selective content <iframe src=\"https://www.youtube.com/embed/\" allowfullscreen></iframe> |
    When I save and close form
    Then I should see "Page has been saved" flash message
    And should see "Selective content"

  Scenario: Create a new Landing Page with invalid twig
    Given I go to Marketing / Landing Pages
    And click "Create Landing Page"
    And I fill "CMS Page Form" with:
      | Titles  | Other page          |
      | Content | {{ widget(\"\"\")}} |
    When I save and close form
    Then I should see only "The entered content contains invalid twig constructions." error message

  Scenario: Create a new Landing Page with link using draggable attribute
    When I go to Marketing / Landing Pages
    And click "Create Landing Page"
    And I fill "CMS Page Form" with:
      | Titles  | Draggable attr                            |
      | Content | <a draggable=\"true\" href=\"#\">Link</a> |
    And I save and close form
    Then I should see "Page has been saved" flash message

  Scenario: Create a new Landing Page with link using target attribute
    Given I go to Marketing / Landing Pages
    And click "Create Landing Page"
    And I fill "CMS Page Form" with:
      | Titles  | Page with link              |
      | Content | <a target=\"_blank\">Link</a> |
    When I save and close form
    Then I should see "Page has been saved" flash message
