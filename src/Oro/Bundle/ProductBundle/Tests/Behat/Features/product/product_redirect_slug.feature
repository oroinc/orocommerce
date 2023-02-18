@fixture-OroProductBundle:product_slug.yml

Feature: Product redirect slug
  In order to have the ability to display a "friendly URL" address for customers
  As an administrator
  I want to be able to add and modify a "slug" to a product

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | User  | second_session |

  Scenario: Set product slugs
    Given I proceed as the Admin
    And login as administrator
    And I set "Default Web Catalog" as default web catalog
    When I go to Products/Products
    And click Edit "SKU1" in grid
    And click on "Product Form Slug Fallbacks"
    And fill "Product Form" with:
      | Name First Slug Use Default | false      |
      | Name First Slug             | acme-rp-17 |
      | PrimaryPrecision            | 1          |
    Then I save form
    And should see "Product has been saved" flash message

  Scenario: Set default node slugs
    Given I go to Marketing/Web Catalogs
    And click "Edit Content Tree" on row "Default Web Catalog" in grid
    When I save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Set content node slugs
    Given I click "MyCategory"
    And click on "Content Node Form Slug Fallbacks"
    When I fill "Content Node Form" with:
      | First Url Slug Use Default | false              |
      | First Url Slug             | new-category-rc-17 |
    And save form
    Then I should see "Content Node has been saved" flash message

  Scenario: Check product url
    Given I proceed as the User
    And I signed in as AmandaRCole@example.org on the store frontend
    When I click "MyCategory"
    And should see "Product1"
    And click "View Details" for "Product1" product
    Then the url should match "/new-category-rc-17/_item/acme-rp-17"

  Scenario: Check search result
    Given I type "SKU1" in "search"
    And click "Search Button"
    And should see "Product1"
    When I click "View Details" for "Product1" product
    Then the url should match "/acme-rp-17"

  Scenario: Change product slug and enable 301 redirect
    Given I proceed as the Admin
    When I go to Products/Products
    And click Edit "SKU1" in grid
    And click on "Product Form Slug Fallbacks"
    And fill "Product Form" with:
      | Name First Slug  | acme-rp-18 |
      | PrimaryPrecision | 1          |
    And save and close form
    And check "Create 301 Redirect from old to new URLs"
    And click "Apply" in modal window
    Then I should see "Product has been saved" flash message

  Scenario: Change category slug and enable 301 redirect
    Given I go to Marketing/Web Catalogs
    And click "Edit Content Tree" on row "Default Web Catalog" in grid
    And click "MyCategory"
    And click on "Content Node Form Slug Fallbacks"
    When fill "Content Node Form" with:
      | First Url Slug | new-category-rc-18 |
    And save form
    And check "Create 301 Redirect from old to new URLs"
    And click "Apply" in modal window
    Then I should see "Content Node has been saved" flash message

  Scenario: Check product url with new slug
    Given I proceed as the User
    When I click "MyCategory"
    And should see "Product1"
    And click "View Details" for "Product1" product
    Then the url should match "/new-category-rc-18/_item/acme-rp-18"

  Scenario: Check 301 redirect
    Given I am on the homepage
    When I am on "/acme-rp-17"
    Then the url should match "/acme-rp-18"
    When I am on "/new-category-rc-17/_item/acme-rp-17"
    Then the url should match "/new-category-rc-18/_item/acme-rp-18"

  Scenario: Check search result with new slug
    Given I type "SKU1" in "search"
    And click "Search Button"
    And should see "Product1"
    When I click "View Details" for "Product1" product
    Then the url should match "/acme-rp-18"

  Scenario: Change product slug field using import
    Given I proceed as the Admin
    And login as administrator
    And I go to Products/Products
    And I open "Products" import tab
    # Do not change or add other fields as this will break the test.
    And fill import file with data:
      | sku  | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | slugPrototypes.default.fallback | slugPrototypes.default.value | slugPrototypes.English (United States).fallback | slugPrototypes.English (United States).value |
      | SKU1 | each                           | 1                              |                                 | product-1                    |                                                 | acme-rp-19                                   |
    And I import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 0, updated: 0, replaced: 1" text

  Scenario: Check search result with new slug
    Given I proceed as the User
    And I am on the homepage
    And I type "SKU1" in "search"
    And click "Search Button"
    And should see "Product1"
    When I click "View Details" for "Product1" product
    Then the url should match "/acme-rp-19"
