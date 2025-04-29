@regression
@feature-BB-25441
@fixture-OroCatalogBundle:category_for_breadcrumbs.yml
Feature: Catalog breadcrumbs trimming based on configuration

  Scenario: Initialize user sessions
    Given sessions active:
      | Admin | system_session |
      | Guest | first_session  |

  Scenario: Guest user sees full breadcrumbs on product pages
    Given I proceed as the Guest
    And I am on the homepage
    When I click "Headlamps" in hamburger menu
    Then I should see that "Breadcrumbs" contains "Headlamps"
    When I click "View Details" for "PSKU1" product
    Then I should see that "Breadcrumbs" contains "220 Lumen Rechargeable Headlamp"
    When I click "All Products"
    Then I should see that "Breadcrumbs" contains "All Products"

  Scenario: Admin enables trimming of the last breadcrumb
    Given I proceed as the Admin
    And I login as administrator
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

  Scenario: Guest user sees that last breadcrumb is trimmed on product pages
    Given I proceed as the Guest
    When I reload the page
    Then I should see that "Breadcrumbs" does not contain "All Products"
    When I click "Headlamps" in hamburger menu
    Then I should see that "Breadcrumbs" does not contain "Headlamps"
    When I click "View Details" for "PSKU1" product
    Then I should see that "Breadcrumbs" does not contain "220 Lumen Rechargeable Headlamp"
