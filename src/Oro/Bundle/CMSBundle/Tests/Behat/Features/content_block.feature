@fixture-OroCMSBundle:CustomerUserFixture.yml
Feature: Content Block
  In order to modify some predefined marketing content on the store frontend
  As an Administrator
  I want to edit the defined content blocks

  Scenario: Create new content block
    Given I login as administrator
    And go to Marketing/ Content Blocks
    And press "Create Content Block"
    And fill "Content Block Form" with:
      |Owner         |Main                         |
      |Alias         |test_alias                   |
      |Titles        |Test Title                   |
      |Enabled       |True                         |
      |Localization  |English                      |
      |Website       |Default                      |
      |Customer Group|Non-Authentificated Visitors |
    When I save and close form
    Then I should see "Content block has been saved" flash message

  Scenario: Block for authenticated non authenticated users
    Given I go to Marketing/ Content Blocks
    And I click edit "home-page-slider" in grid
    And fill "Content Block Form" with:
      |Customer Group|All Customers |
    And I save and close form
    When I am on homepage
    Then I should not see "LOREM IPSUM"
    When I signed in as AmandaRCole@example.org on the store frontend
    Then I should see "LOREM IPSUM"

  Scenario: Block for different customers
    Given I am on dashboard
    And I go to Marketing/ Content Blocks
    And I click edit "home-page-slider" in grid
    And press "Add Content"
    And I fill "Content Variant 1 form" with:
      |Content  |Test block |
      |Customer |Company B  |
    When I save and close form
    Then I should see "Content block has been saved" flash message
    And click logout in user menu
    When I signed in as AmandaRCole@example.org on the store frontend
    Then I should see "LOREM IPSUM"
    When I signed in as NancyJSallee@example.org on the store frontend
    Then I should see "Test block"
