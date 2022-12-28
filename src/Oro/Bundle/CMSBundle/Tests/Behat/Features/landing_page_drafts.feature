@ticket-BB-17908
@fixture-OroCMSBundle:web_catalog.yml
@fixture-OroCMSBundle:CustomerUserFixture.yml
Feature: Landing Page Drafts
  In order to save drafts of landing pages and publish selected draft
  As an Administrator
  I need to be able to changed landing page and create as draft

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create Draft from View Landing Page
    Given I proceed as the Admin
    And I login as administrator
    And I go to Marketing/ Landing Pages
    And I click "Create Landing Page"
    When I fill in Landing Page Titles field with "Test page"
    Then I should see URL Slug field filled with "test-page"
    When I fill in WYSIWYG "CMS Page Content" with "Test content"
    And I save and close form
    Then I should see "Page has been saved" flash message
    When I click "Create draft"
    Then I should see "UiWindow" with elements:
      | Title        | Action Confirmation                                                                                                                                    |
      | Content      | Only the changes from the following fields will be transferred to a draft: metaDescriptions, metaTitles, metaKeywords, slugPrototypes, titles, content |
      | okButton     | Yes                                                                                                                                                    |
      | cancelButton | Cancel                                                                                                                                                 |
    When I click "Yes" in confirmation dialogue
    Then I should not see following page actions:
      | Save As draft |
    When I fill in Landing Page Titles field with "Draft 1"
    Then I should see URL Slug field filled with "draft-1"
    And fill "CMS Page Form" with:
      | Meta Title       | Default Meta Title       |
      | Meta Description | Default Meta Description |
    And I save and close form
    Then I should see "Draft has been saved" flash message
    And I reload the page
    And I should see Landing Page with:
      | Title | Draft 1    |
      | Slugs | [/draft-1] |
    When I click "Edit"
    And I fill in WYSIWYG "CMS Page Content" with "GrapesJS Draft content"
    And I save and close form
    Then I should see "Draft has been saved" flash message
    And I should see "GrapesJS Draft content"
    And I should see available page actions:
      | Duplicate     |
      | Edit          |
      | Delete        |
      | Publish draft |
    When I go to Marketing/ Landing Pages
    And I click view "Test page" in grid
    And I should see following grid:
      | Title   | Slug    | Owner    |
      | Draft 1 | draft-1 | John Doe |
    And I should see following actions for Draft 1 in grid:
      | View          |
      | Edit          |
      | Delete        |
      | Duplicate     |
      | Publish draft |

  Scenario: Check that drafts is not available on Web Catalog
    Given I set "Default Web Catalog" as default web catalog
    When I go to Marketing/ Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click "Save"
    Then I should see "Content Node has been saved" flash message
    When I click "Create Content Node"
    And I fill "Content Node" with:
      | Title | Test Node |
      | Slug  | test-node |
    And I click on "Show Variants Dropdown"
    And I click "Add Landing Page"
    And should not see the following options for "Landing Page" select in form "Content Node Form":
      | Draft 1 |
    And I fill "Content Node Form" with:
      | Landing Page | Test page |
    And I save form
    Then I should see "Content Node has been saved" flash message
    When I go to System/ Configuration
    And follow "System Configuration/Websites/Routing" on configuration sidebar
    And uncheck "Use default" for "Navigation Root" field
    And I click on "Default Web Catalog"
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check that draft is not available on Storefront
    Given I proceed as the Buyer
    When I am on the homepage
    Then I should see "Test Node" in main menu
    When I click "Test Node"
    Then Page title equals to "Test Node"
    And I should be on "/test-node"
    And I should see "Test content"
    And I should not see "GrapesJS Draft content"

  Scenario: When Use "Save as Draft" option Then New Draft is created
    Given I proceed as the Admin
    When I go to Marketing/ Landing Pages
    And I click edit "Test page" in grid
    And I fill in Landing Page Titles field with "Draft 2"
    And I should see URL Slug field filled with "draft-2"
    When I click "Save as draft"
    Then I should see "Draft has been saved" flash message
    And I reload the page
    And I should see Landing Page Draft with:
      | Title | Draft 2    |
      | Slugs | [/draft-2] |
    And I should not see "CMS Page Drafts Grid" grid

  Scenario: Draft is not available in backoffice search
    When I click "Search"
    And I type "Draft" in "search"
    And I click "Search Submit"
    Then I should see "No results were found to match your search."
    And I should see "Try modifying your search criteria or creating a new"

  Scenario: Publish Draft
    When I go to Marketing/ Landing Pages
    And I click view "Test page" in grid
    And I click edit "Draft 1" in grid
    And I fill in Landing Page Titles field with "New Title"
    And I fill in URL Slug field with "new-landing-page"
    And I save and close form
    Then I should see "Draft has been saved" flash message
    When I click "Publish draft"
    And I click "Yes" in confirmation dialogue
    And I reload the page
    Then I should see Landing Page with:
      | Title | New Title    |
      | Slugs | [/new-landing-page] |
    And I should see available page actions:
      | Create draft |
    And I should see following grid:
      | Title   | Slug    | Owner    |
      | Draft 2 | draft-2 | John Doe |
    And number of records in "CMS Page Drafts Grid" should be 1

  Scenario: After "Publish" Draft become a Landing Page and available in the backoffice search
    When I click "Search"
    And type "New Title" in "search"
    Then I should see 1 search suggestions
    When I click "Search Submit"
    Then I should be on Search Result page
    And I should see following search entity types:
      | Type          | N | isSelected |
      | All           | 1 | yes        |
      | Landing Pages | 1 |            |
    And number of records should be 1

  Scenario: Check Landing Page from Published Draft on Storefront
    Given I proceed as the Buyer
    When I reload the page
    And I should be on "/test-node"
    And I should see "GrapesJS Draft content"
    And I should not see "Test content"

  Scenario: Duplicate Draft
    Given I proceed as the Admin
    And I go to Marketing/ Landing Pages
    And I click view "New Title" in grid
    When I click duplicate "Draft 2" in grid
    And I click "Yes" in confirmation dialogue
    Then I should see "Draft has been saved" flash message
    When I save and close form
    Then I should see "Draft has been saved" flash message
    And I reload the page
    And I should see Landing Page Draft with:
      | Title | Draft 2    |
      | Slugs | [/draft-2] |
    When I go to Marketing/ Landing Pages
    And I click view "New Title" in grid
    Then I should see following grid:
      | Title   | Slug    | Owner    |
      | Draft 2 | draft-2 | John Doe |
      | Draft 2 | draft-2 | John Doe |

  Scenario: Save As New Draft
    When click edit "draft-2" in grid
    And I fill in Landing Page Titles field with "D003"
    And I fill in URL Slug field with "d003"
    And I click "Save as new draft"
    Then I should see "Draft has been saved" flash message
    And I should see Landing Page Draft with:
      | Title | D003 |
    When I go to Marketing/ Landing Pages
    And I click view "New Title" in grid
    Then I should see following grid:
      | Title   | Slug    | Owner    |
      | Draft 2 | draft-2 | John Doe |
      | Draft 2 | draft-2 | John Doe |
      | D003    | d003    | John Doe |

  Scenario: Delete Draft
    When click delete "D003" in grid
    And I click "Yes, Delete" in confirmation dialogue
    Then I should see "Landing Page deleted" flash message
    And I should see following grid:
      | Title   | Slug    | Owner    |
      | Draft 2 | draft-2 | John Doe |
      | Draft 2 | draft-2 | John Doe |
    And number of records in "CMS Page Drafts Grid" should be 2
