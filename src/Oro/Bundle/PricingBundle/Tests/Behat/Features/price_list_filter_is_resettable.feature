@fixture-OroPricingBundle:PriceListFilter.yml

Feature: Price list filter is resettable
  ToDo: BAP-16103 Add missing descriptions to the Behat features
  Scenario: Check that Product Prices datagrid is filtered by default price list, but it can resetted to show all
    Given I login as administrator
    And I go to Products/ Products
    And click view "PSKU1" in grid
    And I click "ProductPricesGridFiltersButton"
    And I should see "All" in the "PriceListFilterHint" element
    And I should see following "ProductPricesGrid" grid:
      | Price List         | Quantity | Unit | Value | Currency |
      | Default Price List | 1        | item | 6.00  | USD      |
      | priceList2         | 1        | item | 5.00  | USD      |
    When I check "Default Price List" in "Price List: All" filter in "ProductPricesGrid" strictly
    And I should see "Default Price List"
    And I should not see "priceList2"
    When I reset "Price List" filter on grid "ProductPricesGrid"
    Then I should see "All" in the "PriceListFilterHint" element
    And I should see following records in "ProductPricesGrid":
      | Default Price List |
      | priceList2         |
