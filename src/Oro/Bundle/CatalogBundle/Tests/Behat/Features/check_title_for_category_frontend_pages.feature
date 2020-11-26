@fixture-OroCatalogBundle:categories.yml
Feature: Check title for category frontend pages
    For category pages must be the title of the category
    Name of category must be for the current locale

    Scenario: Check title for category
        Given I am on the homepage
        Then I should see "Lighting Products"
        When I click "Lighting Products"
        Then Page title equals to "Lighting Products"
        When I click "All products"
        Then Page title equals to "All Products"
