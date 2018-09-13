@fixture-OroCMSBundle:CustomerUserFixture.yml
Feature: Content Block content purify
  In order to avoid xss vulnerability
  As an Administrator
  I want to purify text data for Content Block form

  Scenario: Create new content block with tags in text data
    Given I login as administrator
    And go to Marketing / Content Blocks
    And press "Create Content Block"
    And I click "Add Content"
    And fill "Content Block Form" with:
      |Owner         |Main                       |
      |Alias         |test_alias                 |
      |Titles        |Test Title                 |
      |Enabled       |True                       |
      |Localization  |English                    |
      |Website       |Default                    |
      |Customer Group|Non-Authenticated Visitors |
      |Content       |<style>div {display: none;}</style><div onclick=\"alert('test');\">Some Content <script>alert('test')</script></div> |
    When I save and close form
    Then I should see "Content block has been saved" flash message
    And I should not see alert
    And I should see "Some Content"
