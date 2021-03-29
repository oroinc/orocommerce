@ticket-BB-20439
@fixture-OroWebCatalogBundle:customer.yml
@fixture-OroWebCatalogBundle:web_catalog_for_breadcrumbs.yml

Feature: Content Variant tooltip
  In order to show tooltips in content variant
  As site administrator
  I need to be able to see tooltips in content variants

  Scenario: Create sessions
    Given sessions active:
      | Admin | first_session  |

  Scenario: Check the ability to open a tooltip 
    Given I proceed as the Admin
    And I login as administrator
    And I set "Default Web Catalog" as default web catalog
    And I go to Marketing/Web Catalogs
    And I click "Edit Content Tree" on row "Default Web Catalog" in grid
    And I click on "Show Variants Dropdown"
    And I click "Add Category"
    When I click "Sub Categories Tooltip Icon"
    Then I should see "This option can be used to include all products assigned to sub-categories (all levels) of the current category in addition to the products that are assigned directly. The first level sub-categories that have (directly or in any of their sub-categories) at least one product visible to the current user will be shown as category filter options."
