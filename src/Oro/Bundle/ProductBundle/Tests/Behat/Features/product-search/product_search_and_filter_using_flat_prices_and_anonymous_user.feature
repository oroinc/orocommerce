@regression
@ticket-BB-21241
@pricing-storage-flat
@fixture-OroProductBundle:product_search/pricelists.yml
@fixture-OroProductBundle:single_product.yml

Feature: Product search and filter using flat prices and anonymous user
  Check that the products has the appropriate prices, taking into account the price lists of different
  configuration levels

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And login as administrator

  Scenario Outline: Import flat prices
    Given I go to Sales/ Price Lists
    And click view "<Price list name>" in grid
    When I download "Product Price" Data Template file
    And fill template with data:
      | Product SKU | Quantity   | Unit Code | Price   | Currency |
      | PSKU1       | <Quantity> | each      | <Price> | USD      |
    And import file
    Then Email should contains the following "Errors: 0 processed: 1, read: 1, added: 1, updated: 0, replaced: 0" text

    Examples:
      | Price list name   | Quantity | Price |
      | first price list  | 1        | 10    |
      | second price list | 1        | 20    |

  Scenario: Create relation
    Given I go to Customers/ Customer Groups
    And click Edit Non-Authenticated Visitors in grid
    When I fill "Customer Group Form" with:
      | Price List | first price list |
    And save form
    Then I should see "Customer group has been saved" flash message

  Scenario: Create website relation
    Given I go to System/ Websites
    And click "Configuration" on row "Default" in grid
    And follow "Commerce/Catalog/Pricing" on configuration sidebar
    When I fill "PricingConfigurationForm" with:
      | Pricing Default Price List Use Default | false             |
      | Pricing Default Price List             | second price list |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check flat prices in search Autocomplete
    Given I proceed as the Buyer
    And I am on the homepage

    When I type "PSKU1" in "search"
    Then I should see an "Search Autocomplete" element
    And should see "$10.00" in the "Search Autocomplete Product" element
    And click "Search Button"

    When I click "Grid Filters Button"
    And filter "Filter by Price" as between "9" and "11" use "each" unit
    Then number of records in "Product Frontend Grid" should be 1
    And should see "Product 1" in grid "Product Frontend Grid"

