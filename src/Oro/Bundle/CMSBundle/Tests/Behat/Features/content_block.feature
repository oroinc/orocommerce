@behat-test-env
@ticket-BB-19056
@feature-BAP-19790
@fixture-OroCMSBundle:CustomerUserFixture.yml
@fixture-OroCMSBundle:WysiwygRoleFixture.yml
Feature: Content Block
  In order to modify some predefined marketing content on the store frontend
  As an Administrator
  I want to edit the defined content blocks

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create new content block without content variant
    Given I proceed as the Admin
    And I login as administrator
    And go to Marketing/ Content Blocks
    And click "Create Content Block"
    And fill "Content Block Form" with:
      | Owner          | Main                       |
      | Alias          | test_alias                 |
      | Titles         | Test Title                 |
      | Enabled        | True                       |
      | Localization   | English                    |
      | Website        | Default                    |
      | Customer Group | Non-Authenticated Visitors |
    When I save and close form
    Then I should see "Please add at least one content variant."

  Scenario: Create new content block with content variant
    Given I click "Add Content"
    When I save and close form
    Then I should see "Content block has been saved" flash message

  Scenario: Edit user roles
    Given I go to System/User Management/Users
    When click Edit admin in grid
    And I click "Groups and Roles"
    And I fill form with:
      | Roles | [Administrator, WYSIWYG] |
    And I save and close form
    Then I should see "User saved" flash message
    # Relogin for refresh token after change user roles
    And I am logged out

  Scenario: Block for authenticated non authenticated users
    Given login as administrator
    And I go to Marketing/ Content Blocks
    And I click "edit" on row "home-page-slider" in grid
    And fill "Content Block Form" with:
      | Customer Group | All Customers |
    And I save and close form
    Then I should see "Content block has been saved" flash message
    When I proceed as the Buyer
    And I am on homepage
    Then I should not see a "Homepage Slider" element
    When I signed in as AmandaRCole@example.org on the store frontend
    Then I should see a "Homepage Slider" element

  Scenario: Block for different customers
    Given I proceed as the Admin
    And I am on dashboard
    And I go to Marketing/ Content Blocks
    And I click "edit" on row "home-page-slider" in grid
    And click "Add Content"
    And I fill "Content Variant 1 form" with:
      | Customer | Company B  |
    And I fill in WYSIWYG "Content Variant 1 Content" with "Test block"
    When I save and close form
    Then I should see "Content block has been saved" flash message
    And click logout in user menu
    When I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    Then I should see "Best-Priced Medical Supplies"
    When I signed in as NancyJSallee@example.org on the store frontend
    Then I should see "Test block"

  Scenario: Block with different contents
    Given I proceed as the Admin
    And login as administrator
    And go to Marketing/ Content Blocks
    And click "Create Content Block"
    When fill "Content Block Form" with:
      | Owner   | Main             |
      | Alias   | test_block_alias |
      | Titles  | Test Block Title |
      | Enabled | True             |
    And click "Add Content"
    And fill "Content Block Form" with:
      | Content Variant | Test variant 1 |
    And click "Add Content"
    And fill "Content Block Form" with:
      | Content Variant 1 | Test variant 2 |
    And I save and close form
    Then I should see "Content block has been saved" flash message
    And I should see "Test variant 1"
    And I should see "Test variant 2"
    # do save second time to check wysiwyg initialization with data
    And I click "Edit"
    And I save and close form
    And  I should see "Content block has been saved" flash message
    And I should see "Test variant 1"
    And I should see "Test variant 2"

  Scenario: Block with incorrect twig
    Given I go to Marketing/ Content Blocks
    And I click "edit" on row "home-page-slider" in grid
    And I fill in WYSIWYG "Content Variant 1 Content" with "{{ widget(\"\"\")}}"
    When I save and close form
    Then I should see only "The entered content contains invalid twig constructions." error message
