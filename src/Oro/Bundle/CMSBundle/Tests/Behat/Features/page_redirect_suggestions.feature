@regression
@ticket-BB-7986
@automatically-ticket-tagged
Feature: Page redirect suggestions
  In order to make administrator aware of redirect generation process
  As administrator
  I need to be able to know which redirects are generated for current page if I change slugs

  Scenario: "Page redirect suggestions 1" > Slug prototypes should be changed according to slugs and display in confirmation dialog. PRIORITY - MAJOR
    Given I login as administrator
    And go to Marketing/ Landing Pages
    When I click "Create Landing Page"
    And I fill in Landing Page Titles field with "About"
    And I fill in URL Slug field with "about"
    And I click "Save and Close"
    And I click "Edit"
    And I should see URL Slug field filled with "about-1"
    And I fill in URL Slug field with "other-slug"
    And I click "Save and Close"
    Then I should see "\"/about-1\" to the \"/other-slug\" for \"Default Value\""
    And I click "Cancel" in modal window
