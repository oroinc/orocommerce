@ticket-BB-23050
@fixture-OroCMSBundle:web_catalog.yml
Feature: Content Node as a home page

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Guest | second_session |
    And I set "Homepage Content" before content for "Homepage" page

  Scenario: Update Content Node with Landing Page
    Given I proceed as the Admin
    When I login as administrator
    And I go to Marketing / Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Remove Variant Button"
    And I click on "Show Variants Dropdown"
    And I click "Add Landing Page"
    And I fill "Content Node Form" with:
      | Landing Page | Cookie Policy |
    And I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Check that Homepage system config is not available
    When I go to System / Configuration
    And I follow "System Configuration/Websites/Routing" on configuration sidebar
    Then I should see a "Homepage" element
    And "Routing Settings Form" must contains values:
      | Homepage | Homepage |
    When I set "Default Web Catalog" as default web catalog
    Then I should not see a "Homepage" element

  Scenario: Check home page content
    Given I proceed as the Guest
    When I am on the homepage
    Then I should not see "Homepage Content"
    And I should see "Default Web Catalog"
    And I should see "This is the Cookie Policy for OroCommerce application."

  Scenario: Update Content Node with "Do not render title"
    Given I proceed as the Admin
    When I go to Marketing / Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click "First Content Variant Expand Button"
    And I fill "Content Node Form" with:
      | Do not render title | true |
    And I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Check that content node title is not visible
    Given I proceed as the Guest
    When I reload the page
    Then I should see "This is the Cookie Policy for OroCommerce application."
    And I should not see "Default Web Catalog"
    And I should not see "Homepage Content"
