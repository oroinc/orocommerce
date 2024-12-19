@regression
@ticket-BB-24819
@fixture-OroWebCatalogBundle:content_variant_localization.yml

Feature: Content variant localization
  Check if the localization of the page variant matches the url.

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Enable German localization
    Given I proceed as the Admin
    And login as administrator
    And go to System / Configuration
    When I follow "System Configuration/General Setup/Localization" on configuration sidebar
    And fill form with:
      | Enabled Localizations | [English, German_Loc]   |
      | Default Localization  | English (United States) |
    And submit form
    Then I should see "Configuration saved" flash message

  Scenario: Create default web catalog
    Given I go to Marketing/ Web Catalogs
    When I click "Create Web Catalog"
    And fill form with:
      | Name | Default Web Catalog |
    And click "Save and Close"
    Then I should see "Web Catalog has been saved" flash message

  Scenario: Create root node
    Given I click "Edit Content Tree"
    When I fill "Content Node Form" with:
      | Titles | Root node |
    And click "Add System Page"
    And save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Create node with variants
    Given I click "Create Content Node"
    And click "URL Slug Fallback Status"
    And fill "Content Node" with:
      | Title                                          | Product Variant |
      | Slug                                           | product-variant |
      | URL Slugs German use fallback                  | false           |
      | URL Slugs German value                         | test-node-de    |
      | URL Slugs English (United States) use fallback | false           |
      | URL Slugs English (United States) value        | test-node-en    |
    And click on "Show Variants Dropdown"
    And click "Add Landing Page"
    And fill "Content Node Form" with:
      | Landing Page | Landing Page EN |
    And click on "Show Variants Dropdown"
    And click "Add Landing Page"
    And fill "Content Node Form" with:
      | Landing Page                                    | Landing Page DE |
      | First Content Variant Restrictions Localization | German_Loc      |
    When I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Set root navigation in system config
    Given I go to System/ Configuration
    And follow "System Configuration/Websites/Routing" on configuration sidebar
    And uncheck "Use default" for "Web Catalog" field
    And fill "Routing Settings Form" with:
      | Web Catalog | Default Web Catalog |
    When I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Switch localization and check landing page content localization
    Given I proceed as the User
    And I am on homepage
    When I select "German Localization" localization
    And click "Product Variant" in hamburger menu
    Then I should see "Landing page DE"
    And remember current URL
    When I select "English (United States)" localization
    Then I should see "Landing page EN"
    When I follow remembered URL
    Then I should see "Landing page DE"
