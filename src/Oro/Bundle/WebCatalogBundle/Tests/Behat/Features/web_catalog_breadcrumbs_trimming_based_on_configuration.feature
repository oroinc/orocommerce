@regression
@feature-BB-25441
@fixture-OroWebCatalogBundle:web_catalog_for_breadcrumbs.yml
Feature: Web catalog breadcrumbs trimming based on configuration

  Scenario: Initialize user sessions
    Given sessions active:
      | Admin | system_session |
      | Guest | first_session  |

  Scenario: Create content nodes in web catalog and mark web catalog as default
    Given I proceed as the Admin
    And I login as administrator
    And I set "Default Web Catalog" as default web catalog
    And I go to Marketing/ Web Catalogs
    And I click view Default Web Catalog in grid
    And I click "Edit Content Tree"
    And I fill "Content Node" with:
      | Title | Lighting Products |
    And I click "Add System Page"
    And I click "Save"
    And I click "Create Content Node"
    And I fill "Content Node" with:
      | Title | Headlamps |
      | Slug  | headlamps |
    And I click on "Show Variants Dropdown"
    And I click "Add Category"
    And I click "Headlamps"
    And I click "Save"
    And I click "Lighting Products"
    And I click "Create Content Node"
    And I fill "Content Node" with:
      | Title | Product page as Content Node |
      | Slug  | product-page-as-content-node |
    And I click on "Show Variants Dropdown"
    And I click "Add Product Page"
    And I fill "Content Variant" with:
      | Product | 220 Lumen Rechargeable Headlamp |
    And I click "Save"

  Scenario: Guest user sees full breadcrumbs on product pages based on web catalog
    Given I proceed as the Guest
    And I am on the homepage
    When I click on "Headlamps category"
    Then I should see "All Products / Headlamps" in breadcrumbs in the storefront
    When I click "View Details" for "PSKU1" product
    Then I should see "All Products / Headlamps / 220 Lumen Rechargeable Headlamp" in breadcrumbs in the storefront
    When I click "All Products"
    Then I should see "All Products" in breadcrumbs in the storefront

  Scenario: Administrator enables config option to exclude last breadcrumb on product pages
    Given I proceed as the Admin
    When I go to System / Configuration
    And I follow "Commerce/Product/SEO" on configuration sidebar
    And uncheck "Use default" for "Exclude Current Page in Breadcrumbs on All Pages" field
    And I check "Exclude Current Page in Breadcrumbs on All Pages"
    And uncheck "Use default" for "Exclude Current Page in Breadcrumbs on Product View" field
    And I check "Exclude Current Page in Breadcrumbs on Product View"
    And uncheck "Use default" for "Hides the breadcrumbs block entirely when it contains a single item" field
    And I check "Hides the breadcrumbs block entirely when it contains a single item"
    And save form
    Then I should see "Configuration saved" flash message

  Scenario: Guest user sees that last breadcrumb is trimmed on product pages based on web catalog
    Given I proceed as the Guest
    When I reload the page
    Then I should see that "Breadcrumbs" does not contain "All Products"
    When I click "Headlamps"
    Then I should see "Lighting Products" in breadcrumbs in the storefront
    When I click "View Details" for "PSKU1" product
    Then I should see "Lighting Products / Headlamps" in breadcrumbs in the storefront
