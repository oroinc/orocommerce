@ticket-BAP-17313
@fixture-OroPricingBundle:ProductPricesWithMultipleCurrencies.yml
@fixture-OroPricingBundle:ProductPricesWithAdditionalMultipleCurrencies.yml

Feature: Prices on Products Datagrid
  In order to ensure price column is loaded properly when enabled after being disabled
  As an Administrator
  I need to disable price column, then enable and check that it is loaded

  Scenario: Disable price column on products datagrid
    Given I login as administrator
    And I go to Products/ Products
    And I shouldn't see "Price (USD)" column in grid
    And I shouldn't see "Price (EUR)" column in grid
    When I check "USD"
    And I should see "Price (USD)" column in grid
    And I hide column Price (USD) in grid
    Then I shouldn't see "Price (USD)" column in grid

  Scenario: Ensure price column on products datagrid can be loaded when enabled
    Given I show column Price (USD) in grid
    When I filter "Price (USD)" as Equals "23"
    Then I should see "Price (USD)" column in grid
    And I should see that "$23.00" is in 1 row
    And I reset "Price (USD)" filter

  Scenario: Ensure price filter with item on products datagrid can be loaded when enabled
    Given I show filter "Price (USD/each)" in "Products Grid" grid
    And I show filter "Price (USD/item)" in "Products Grid" grid
    When I filter "Price (USD/each)" as Equals "23"
    And I filter "Price (USD/item)" as Equals "13"
    Then I should see following grid:
      | SKU   | Name      |
      | PSKU1 | Product 1 |

  Scenario: Ensure price column with item on products datagrid can be loaded when enabled
    Given I reload the page
    When I show column Price (USD/each) in grid
    And I show column Price (USD/item) in grid
    Then I should see "Price (USD/each)" column in grid
    And I should see "Price (USD/item)" column in grid
    And I should see following grid:
      | SKU   | Name      | Price (USD/each) | Price (USD/item) |
      | PSKU1 | Product 1 | $23.00           | $13.00           |
