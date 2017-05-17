@fixture-category_for_breadcrumbs.yml
Feature: Product view page breadcrumbs

  Scenario: Breadcrumbs should be built based on category
    Given I am on the homepage
    And I click "Headlamps"
    When I click "220 Lumen Rechargeable Headlamp"
    Then I should see "Products categories / Headlamps / 220 Lumen Rechargeable Headlamp"
    When I click on "Products categories"
    Then Page title equals to "Catalog"
