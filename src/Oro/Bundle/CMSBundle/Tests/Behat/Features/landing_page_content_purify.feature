@feature-BB-17656
@ticket-BB-18771
@ticket-BB-18284
@waf-skip
@fixture-OroCMSBundle:WysiwygRoleFixture.yml
@behat-test-env
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
      | Content | Secure content <button>sample button</button> |
    And I save and close form
    Then I should see validation errors:
      | Content | Please remove not permitted HTML-tags in the content field: BUTTON |
    When I fill "CMS Page Form" with:
      | Titles  | Other page                      |
      | Content | Secure content <img src=\"#\"/> |
    And I save and close form
    Then I should see only "Please remove not permitted HTML-tags in the content field: - Attributes on \"<img>\" should be transformed from src to src and alt (near <img src=\"#\"/>...)." error message
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
      | Content | Selective content <button>sample button</button> |
    When I save and close form
    Then I should see "Page has been saved" flash message
    And should see "Selective content"

  Scenario: Create Landing Page with multilines content
    Given I go to Marketing / Landing Pages
    And click "Create Landing Page"
    And I fill "CMS Page Form" with:
      | Titles | Page with multiline content |
    And I fill in WYSIWYG "CMS Page Content" with "<div data-title=\"home-page-slider\" data-type=\"image_slider\" class=\"content-widget content-placeholder\">{{ widget(\"home-page-slider\") }}\r\n  </div>\r\n  <div id=\"i7nk\">\r\n  <iframe allowfullscreen=\"allowfullscreen\" id=\"ikah\" src=\"https://www.youtube.com/embed/D733JoYu92k?&loop=1&playlist=D7Q3JoYu92k&modestbranding=1\" allow=\"accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture\" width=\"1920\" height=\"837\" frameborder=\"0\"></iframe>\r\n  <div id=\"irn2\">\r\n  <iframe src=\"https://www.w3schools.com\"></iframe>\r\n  <div>\r\n  <br/>\r\n  </div>\r\n  <a href=\"javascript:alert(1)\" class=\"link\">test</a>\r\n  </div>\r\n  </div>"
    When I save and close form
    Then I should see "Please remove not permitted HTML-tags in the content field: - \"src\" attribute on \"<iframe>\" should be removed (near <iframe frameborder=\"0\" s...); - \"href\" attribute on \"<a>\" should be removed (near <a href=\"javascript:alert...)."

  Scenario: Create a new Landing Page with invalid twig
    Given I go to Marketing / Landing Pages
    And click "Create Landing Page"
    And I fill "CMS Page Form" with:
      | Titles  | Other page          |
      | Content | {{ widget(\"\"\")}} |
    When I save and close form
    Then I should see only "The entered content contains invalid twig constructions." error message

  Scenario: Create a new Landing Page with link using target attribute
    Given I go to Marketing / Landing Pages
    And click "Create Landing Page"
    And I fill "CMS Page Form" with:
      | Titles  | Page with link              |
      | Content | <a target=\"_blank\">Link</a> |
    When I save and close form
    Then I should see "Page has been saved" flash message
