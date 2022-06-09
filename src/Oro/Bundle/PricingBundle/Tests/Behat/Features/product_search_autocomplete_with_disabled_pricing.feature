@regression
@ticket-BB-21286
@fixture-OroProductBundle:products.yml
Feature: Product search autocomplete with disabled pricing
  In order to be able to search for products on storefront
  As a Buyer
  I want to be able to search for products when Oro Pricing is disabled

  Scenario: Feature Background
    Given I disable configuration options:
      | oro_pricing.feature_enabled |

  Scenario: Check search autocomplete without prices
    When I am on the homepage
    And I type "PSKU1" in "search"
    Then I should not see "There was an error performing the requested operation." flash message
    And I should see "PSKU1" in the "Search Autocomplete Product" element
