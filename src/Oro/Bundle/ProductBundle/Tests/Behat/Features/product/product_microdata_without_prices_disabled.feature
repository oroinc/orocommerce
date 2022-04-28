@regression
@ticket-BB-21285
@fixture-OroProductBundle:products.yml
Feature: Product microdata without prices disabled
  In order to prevent products being excluded from search results, due to prices are missing in the product microdata definition
  As an administrator
  I want to be able to disable product microdata definition when prices are missing

  Scenario: Feature Background
    Given sessions active:
      | Buyer | second_session |

  Scenario: Check product type microdata definition in product item
    Given I proceed as the Buyer
    And I am on the homepage
    And I type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "Product Type Microdata Declaration" element inside "ProductItem" element

  Scenario: Check no microdata definition with switched off Oro Pricing feature
    And I disable configuration options:
      | oro_pricing.feature_enabled |
    And I reload the page
    Then I should not see "Product Type Microdata Declaration" element inside "ProductItem" element

  Scenario: Check microdata definition with switched off protection for products microdata without prices
    And I disable configuration options:
      | oro_product.microdata_without_prices_disabled |
    And I reload the page
    Then I should see "Product Type Microdata Declaration" element inside "ProductItem" element
