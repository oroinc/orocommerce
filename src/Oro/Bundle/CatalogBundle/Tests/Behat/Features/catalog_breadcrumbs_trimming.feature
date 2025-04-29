@regression
@feature-BB-25441
@fixture-OroCatalogBundle:category_for_breadcrumbs.yml
Feature: Catalog breadcrumbs trimming

  Scenario: Guest user sees that last breadcrumb is trimmed on product pages based on catalog
    Given I am on the homepage
    When I click "Headlamps" in hamburger menu
    Then I should see that "Breadcrumbs" does not contain "Headlamps"
    When I click "Headlamps" in hamburger menu
    And I click "View Details" for "PSKU1" product
    Then I should see that "Breadcrumbs" does not contain "220 Lumen Rechargeable Headlamp"
    When I click "All Products"
    Then I should see that "Breadcrumbs" does not contain "All Products"
