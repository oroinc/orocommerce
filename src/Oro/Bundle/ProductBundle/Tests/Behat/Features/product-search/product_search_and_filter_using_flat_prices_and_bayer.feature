@regression
@ticket-BB-21241
@pricing-storage-flat
@fixture-OroProductBundle:product_search/pricelists.yml
@fixture-OroProductBundle:single_product.yml

Feature: Product search and filter using flat prices and bayer
  Сheck whether the pricing on the storefront is appropriate, the filters and product search work correctly.

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Add new price to default price list
    Given I proceed as the Admin
    And login as administrator
    And go to Products/Products
    When I click Edit PSKU1 in grid
    And click "AddPrice"
    And fill "ProductPriceForm" with:
      | Price List | Default Price List |
      | Quantity   | 1                  |
      | Value      | 5                  |
      | Currency   | $                  |
    And submit form
    Then I should see "Product has been saved" flash message

  Scenario: Check flat price after product price update
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on the homepage
    When I type "PSKU1" in "search"
    Then I should see an "Search Autocomplete" element
    And should see "$5.00" in the "Search Autocomplete Product" element
    When click "Search Button"
    Then number of records in "Product Frontend Grid" should be 1
    And should see "Your Price: $5.00 / each" for "PSKU1" product

  Scenario: Create relation
    Given I proceed as the Admin
    And go to Customers/Customers
    When I click Edit AmandaRCole in grid
    And fill "Customer Form" with:
      | Price List | first price list |
    And save and close form
    Then I should see "Customer has been saved" flash message

  Scenario Outline: Import flat prices in price list
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
      | first price list  | 5        | 50    |
      | second price list | 1        | 10    |

  Scenario: Check flat product price after import
    Given I proceed as the Buyer
    When I type "PSKU1" in "search"
    Then I should see an "Search Autocomplete" element
    And should see "$50.00" in the "Search Autocomplete Product" element
    When I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 1
    And should see "PSKU1" product
    And should see "Your Price: $50.00 / each" for "PSKU1" product

  Scenario: Change customer relation
    Given I proceed as the Admin
    And go to Customers/Customers
    When I click Edit AmandaRCole in grid
    And fill "Customer Form" with:
      | Price List | second price list |
    And save and close form
    Then I should see "Customer has been saved" flash message

  Scenario: Check flat product price after relation update
    Given I proceed as the Buyer
    When I type "PSKU1" in "search"
    Then I should see an "Search Autocomplete" element
    And should see "$10.00" in the "Search Autocomplete Product" element
    When I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 1
    And should see "PSKU1" product
    And should see "Your Price: $10.00 / each" for "PSKU1" product

  Scenario: Change price list system config
    Given I proceed as the Admin
    And go to Customers/Customers
    When I click Edit AmandaRCole in grid
    And fill "Customer Form" with:
      | Price List |  |
    And save and close form
    Then I should see "Customer has been saved" flash message

    And go to System/ Websites
    When I click "Configuration" on row "Default" in grid
    And follow "Commerce/Catalog/Pricing" on configuration sidebar
    And fill "PricingConfigurationForm" with:
      | Pricing Default Price List Use Default | false            |
      | Pricing Default Price List             | first price list |
    And click "Save settings"
    Then I should see "Configuration saved" flash message

  Scenario: Check the flat product price after system configuration update
    Given I proceed as the Buyer
    When I type "PSKU1" in "search"
    Then I should see an "Search Autocomplete" element
    And should see "$50.00" in the "Search Autocomplete Product" element
    When I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 1
    And should see "PSKU1" product
    And should see "Your Price: $50.00 / each" for "PSKU1" product

  Scenario: Change price list rules
    Given I proceed as the Admin
    And go to Sales/ Price Lists
    When I click edit "first price list" in grid
    And fill form with:
      | Rule | product.id > 0 |
    And click "Add Price Calculation Rules"
    And click "Enter expression unit"
    And click "Enter expression currency"
    And fill "Price Calculation Rules Form" with:
      | Price for quantity | 1                             |
      | Price Unit         | pricelist[3].prices.unit      |
      | Price Currency     | pricelist[3].prices.currency  |
      | Calculate As       | pricelist[3].prices.value * 2 |
      | Priority           | 10                            |
    And save and close form
    Then I should see "Price List has been saved" flash message

  Scenario: Check the flat product price after rule update
    Given I proceed as the Buyer
    When I type "PSKU1" in "search"
    Then I should see an "Search Autocomplete" element
    And should see "$20.00" in the "Search Autocomplete Product" element
    When I click "Search Button"
    Then number of records in "Product Frontend Grid" should be 1
    And should see "PSKU1" product
    And should see "Your Price: $20.00 / each" for "PSKU1" product

  Scenario: Check product grid flat price filter
    Given I click "Grid Filters Button"
    When I filter "Filter by Price" as between "1" and "18" use "each" unit
    Then number of records in "Product Frontend Grid" should be 0

    When I filter "Filter by Price" as between "19" and "21" use "each" unit
    Then number of records in "Product Frontend Grid" should be 1
    And should see "Product 1" in grid "Product Frontend Grid"
