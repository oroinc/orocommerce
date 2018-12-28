@ticket-BB-15787
@fixture-OroWebCatalogBundle:customer.yml
@fixture-OroWebCatalogBundle:web_catalog_for_breadcrumbs.yml
Feature: Content Variant restrictions
  In order to show expected content variant
  As site administrator
  I need to be able to set restrictions for content variants

  Scenario: Logged in as buyer and manager on different window sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Non-default Content Variant must have not empty restrictions
    Given I proceed as the Admin
    And I login as administrator
    And I set "Default Web Catalog" as default web catalog
    And I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | Titles            | Web Catalog Root                        |
      | System Page Route | Oro Frontend Root (Welcome - Home page) |
    When I save form
    Then I should see "Content Node has been saved" flash message
    And I click "Create Content Node"
    And I click on "Show Variants Dropdown"
    And I click "Add System Page"
    And I fill "Content Node Form" with:
      | Titles            | Contact Us Node                                   |
      | Url Slug          | contact-us-node                                   |
      | System Page Route | Oro Contactus Bridge Contact Us Page (Contact Us) |
    And I click on "Show Variants Dropdown"
    And I click "Add Product Page"
    And I fill "Content Node Form" with:
      | Product | 220 Lumen Rechargeable Headlamp |
    When I click "Save"
    Then I should see "Restriction rules must not be empty"

  Scenario: Create Restricted Content Node
    Given I uncheck "Inherit Parent" element
    And I fill "Content Node Form" with:
      | Content Node Restrictions Customer          | Company A |
      | First Content Variant Restrictions Customer | Company A |
    When I click "Save"
    Then I should see "Content Node has been saved" flash message

  Scenario: Restricted Content Variant not accessible by not allowed customer user
    Given I proceed as the Buyer
    And I am on the homepage
    And I should not see "Contact Us Node"
    When I am on "/contact-us-node"
    Then I should see "404 Not Found"

  Scenario: Restricted Content Variant is accessible by allowed customer user
    Given I signed in as AmandaRCole@example.org on the store frontend
    And I should see "Contact Us Node"
    When I am on "/contact-us-node"
    Then I should not see "404 Not Found"
    And Page title equals to "Contact Us Node"
    And I should see "PSKU1"

  Scenario: Content node restrictions validation
    Given I proceed as the Admin
    When I click on "Content Node Remove First Restriction"
    And I click "Save"
    Then I should see "Content node must have at least one restriction"

  Scenario: Saved content variant restrictions validation
    When I click on "First Content Variant Remove First Restriction"
    And I click "Save"
    Then I should see "Restriction rules must not be empty"
