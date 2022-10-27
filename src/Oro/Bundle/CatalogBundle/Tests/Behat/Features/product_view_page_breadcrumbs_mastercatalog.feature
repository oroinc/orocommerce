@regression
@fixture-OroCatalogBundle:category_for_breadcrumbs.yml
Feature: Product view page breadcrumbs mastercatalog
  As a User
  I want be sure
  That breadcrumbs for the mastercatalog are work correctly

  Scenario: Breadcrumbs should be built based on category
    Given I am on the homepage
    And I click "Headlamps"
    When I click "View Details" for "PSKU1" product
    Then I should see "All Products / Headlamps / 220 Lumen Rechargeable Headlamp"
    When I click on "All Products"
    Then Page title equals to "All Products"
