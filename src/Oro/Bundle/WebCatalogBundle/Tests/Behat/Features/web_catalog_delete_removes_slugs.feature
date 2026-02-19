@regression
@ticket-BB-26822
@fixture-OroWebCatalogBundle:web_catalog_with_landing_pages.yml

Feature: Web Catalog delete removes slugs
  In order to keep database clean from orphan slugs
  As an Administrator
  I need slugs to be removed when WebCatalog is deleted

  Scenario: Create different window sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Create content tree for default web catalog
    Given I proceed as the Admin
    And I login as administrator
    And I go to Marketing/ Web Catalogs
    And I click view Default Web Catalog in grid
    And I click "Edit Content Tree"
    And I click on "Show Variants Dropdown"
    And I click "Add Landing Page"
    And I fill "Content Node Form" with:
      | Titles       | First Root         |
      | Landing Page | First Landing Page |
    When I save form
    Then I should see "Content Node has been saved" flash message
    When I click "Create Content Node"
    And I click on "Show Variants Dropdown"
    And I click "Add Landing Page"
    And I fill "Content Node Form" with:
      | Titles       | First Child         |
      | Url Slug     | first-child         |
      | Landing Page | Second Landing Page |
    And I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Set default web catalog as active
    Given I set "Default Web Catalog" as default web catalog

  Scenario: Verify first web catalog pages are accessible on storefront
    Given I proceed as the Buyer
    When I am on the homepage
    Then I should see "FIRST LANDING PAGE CONTENT"
    When I am on "/first-child"
    And I should see "SECOND LANDING PAGE CONTENT"

  Scenario: Create second web catalog
    Given I proceed as the Admin
    And I go to Marketing/ Web Catalogs
    And I click "Create Web Catalog"
    And I fill form with:
      | Name | Additional Web Catalog |
    When I click "Save and Close"
    Then I should see "Web Catalog has been saved" flash message

  Scenario: Create content tree for second web catalog
    Given I click "Edit Content Tree"
    And I click on "Show Variants Dropdown"
    And I click "Add Landing Page"
    And I fill "Content Node Form" with:
      | Titles       | Second Root        |
      | Landing Page | Third Landing Page |
    When I save form
    Then I should see "Content Node has been saved" flash message
    When I click "Create Content Node"
    And I click on "Show Variants Dropdown"
    And I click "Add Landing Page"
    And I fill "Content Node Form" with:
      | Titles       | Second Child        |
      | Url Slug     | second-child        |
      | Landing Page | Fourth Landing Page |
    And I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Set second web catalog as default with navigation root
    Given I go to System/ Configuration
    When I follow "System Configuration/Websites/Routing" on configuration sidebar
    And uncheck "Use default" for "Web Catalog" field
    And I fill "Routing Settings Form" with:
      | Web Catalog | Additional Web Catalog |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Verify second web catalog pages are accessible on storefront
    Given I proceed as the Buyer
    When I am on the homepage
    Then I should see "THIRD LANDING PAGE CONTENT"
    When I am on "/second-child"
    And I should see "FOURTH LANDING PAGE CONTENT"

  Scenario: Remove web catalog and set landing page as homepage
    Given I proceed as the Admin
    And I go to System/ Configuration
    When I follow "System Configuration/Websites/Routing" on configuration sidebar
    And I fill "Routing Settings Form" with:
      | Web Catalog | |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message
    When I fill "Routing Settings Form" with:
      | Homepage | Fifth Landing Page |
    And I click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Verify homepage shows fifth landing page
    Given I proceed as the Buyer
    When I am on the homepage
    Then I should see "FIFTH LANDING PAGE CONTENT"

  Scenario: Delete second web catalog
    Given I proceed as the Admin
    And I go to Marketing/ Web Catalogs
    And I click delete "Additional Web Catalog" in grid
    When I click "Yes, Delete" in confirmation dialogue
    Then I should see "Web Catalog deleted" flash message
    And I should not see "Additional Web Catalog"
    And I should see "Default Web Catalog"

  Scenario: Verify homepage still works correctly after deletion
    Given I proceed as the Buyer
    When I am on the homepage
    Then I should see "FIFTH LANDING PAGE CONTENT"
