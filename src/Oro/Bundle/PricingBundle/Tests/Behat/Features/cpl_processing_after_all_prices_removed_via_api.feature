@regression
@pricing-storage-combined
@ticket-BB-26237
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroProductBundle:ProductsExportFixture.yml

Feature: CPL processing after all prices removed via API

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | API   | second_session |

  Scenario: Prepare API sandbox session
    Given I enable API
    And I proceed as the API
    And I login as administrator
    When I go to "/admin/api/doc"
    And I click on "API List Operations for productprices"

  Scenario: Prepare Admin session
    Given I proceed as the Admin
    And I login as administrator

  Scenario Outline: Remember product IDs
    When I go to Products/ Products
    And I click view <SKU> in grid
    And I remember ID from current URL as "<VAR>"

    Examples:
      | SKU   | VAR         |
      | PSKU2 | PRODUCT2.id |
      | PSKU3 | PRODUCT3.id |
      | PSKU4 | PRODUCT4.id |
      | PSKU5 | PRODUCT5.id |

  Scenario: Create Price List
    When I go to Sales/ Price Lists
    And I click "Create Price List"
    And I fill "Price List Form" with:
      | Name       | TESTPL        |
      | Currencies | US Dollar ($) |
      | Active     | true          |
    And I save and close form
    Then I should see "Price List has been saved" flash message
    And I remember ID from current URL as "TESTPL.id"

  Scenario: Add product price
    Given I click "Add Product Price"
    When I fill "Add Product Price Form" with:
      | Product  | PSKU1 |
      | Quantity | 1     |
      | Unit     | item  |
      | Price    | 2     |
    And I click "Save"
    Then I should see "Product Price has been added" flash message

  Scenario: Assign Price List to customer
    When I go to Customers/Customers
    And click Edit AmandaRCole in grid
    And I fill form with:
      | Fallback | Current customer only |
    And I click "Add Price List"
    And I choose Price List "TESTPL" in 1 row
    And I submit form
    Then I should see "Customer has been saved" flash message

  Scenario: Check price available in price debug
    When I go to Sales/Price Calculation Details
    And I filter SKU as Contains "PSKU1"
    And fill "Price Calculation Details Grid Sidebar" with:
      | Website  | Default     |
      | Customer | AmandaRCole |
    And click on PSKU1 in grid
    And I should see next prices for "Customer Prices":
      | Item (USD) |
      | 1 $2.00    |

  Scenario: Check prices imported to not empty price list are added as expected
    When I go to Sales/ Price Lists
    And click View TESTPL in grid
    And I download "ProductPrice" Data Template file
    And I fill template with data:
      | Product SKU | Quantity | Unit Code | Price | Currency |
      | PSKU2       | 1        | item      | 20    | USD      |
      | PSKU3       | 1        | item      | 31    | USD      |
    And I import file
    And I reload the page
    Then I should see following grid:
      | Product SKU | Product name | Quantity | Unit | Value | Currency |
      | PSKU1       | Product 1    | 1        | item | 2.00  | USD      |
      | PSKU2       | Product 2    | 1        | item | 20.00 | USD      |
      | PSKU3       | Product 3    | 1        | item | 31.00 | USD      |

  Scenario Outline: Check price available in price debug
    When I go to Sales/Price Calculation Details
    And I filter SKU as Contains "<SKU>"
    And fill "Price Calculation Details Grid Sidebar" with:
      | Website  | Default     |
      | Customer | AmandaRCole |
    And click on <SKU> in grid
    And I should see next prices for "Customer Prices":
      | Item (USD) |
      | 1 <Price>  |

    Examples:
      | SKU   | Price  |
      | PSKU1 | $2.00  |
      | PSKU2 | $20.00 |
      | PSKU3 | $31.00 |

  Scenario: Delete Prices with API
    Given I proceed as the API
    # Expand section with 1st click
    When I click on "API Delete Product Prices"
    And I click on "API Active Section Sandbox"
    And I type "$TESTPL.id$" in "API PriceList filter value"
    And I click on "API Try"
    Then I should see "204 success"

  Scenario: Add 2 Prices with Batch API to clean PL
    # Collapse section with 2nd click
    When I click on "API Delete Product Prices"
    And I click on "API Create or update a list of Product Prices"
    And I click on "API Active Section Sandbox"
    And I type '{"data":[{"type":"productprices","attributes":{"quantity":1,"value":"102.00","currency":"USD"},"relationships":{"priceList":{"data":{"type":"pricelists","id":"$TESTPL.id$"}},"product":{"data":{"type":"products","id":"$PRODUCT2.id$"}},"unit":{"data":{"type":"productunits","id":"item"}}}},{"type":"productprices","attributes":{"quantity":1,"value":"103.00","currency":"USD"},"relationships":{"priceList":{"data":{"type":"pricelists","id":"$TESTPL.id$"}},"product":{"data":{"type":"products","id":"$PRODUCT3.id$"}},"unit":{"data":{"type":"productunits","id":"item"}}}}]}' in "API Content"
    And I click on "API Try"
    Then I should see "202 success"

  Scenario: Check PSKU1 was removed from CPL
    Given I proceed as the Admin
    When I go to Sales/Price Calculation Details
    And I filter SKU as Contains "PSKU1"
    And fill "Price Calculation Details Grid Sidebar" with:
      | Website  | Default     |
      | Customer | AmandaRCole |
    And click on PSKU1 in grid
    And I should see "Customer Prices No Prices"

  Scenario Outline: Check price available in price debug
    When I go to Sales/Price Calculation Details
    And I filter SKU as Contains "<SKU>"
    And fill "Price Calculation Details Grid Sidebar" with:
      | Website  | Default     |
      | Customer | AmandaRCole |
    And click on <SKU> in grid
    And I should see next prices for "Customer Prices":
      | Item (USD) |
      | 1 <Price>  |

    Examples:
      | SKU   | Price   |
      | PSKU2 | $102.00 |
      | PSKU3 | $103.00 |

  Scenario: Add 2 Prices with Batch API to not-clean PL
    Given I proceed as the API
    When I type '{"data":[{"type":"productprices","attributes":{"quantity":1,"value":"104.00","currency":"USD"},"relationships":{"priceList":{"data":{"type":"pricelists","id":"$TESTPL.id$"}},"product":{"data":{"type":"products","id":"$PRODUCT4.id$"}},"unit":{"data":{"type":"productunits","id":"item"}}}},{"type":"productprices","attributes":{"quantity":1,"value":"105.00","currency":"USD"},"relationships":{"priceList":{"data":{"type":"pricelists","id":"$TESTPL.id$"}},"product":{"data":{"type":"products","id":"$PRODUCT5.id$"}},"unit":{"data":{"type":"productunits","id":"item"}}}}]}' in "API Content"
    And I click on "API Try"
    Then I should see "202 success"

  Scenario: Check prices added via Batch API to not empty price list are added as expected
    Given I proceed as the Admin
    When I go to Sales/ Price Lists
    And click View TESTPL in grid
    Then I should see following grid:
      | Product SKU | Product name       | Quantity | Unit | Value  | Currency |
      | PSKU2       | Product 2          | 1        | item | 102.00 | USD      |
      | PSKU3       | Product 3          | 1        | item | 103.00 | USD      |
      | PSKU4       | Product 4          | 1        | item | 104.00 | USD      |
      | PSKU5       | Product5(disabled) | 1        | item | 105.00 | USD      |

  Scenario Outline: Check price available in price debug
    When I go to Sales/Price Calculation Details
    And I filter SKU as Contains "<SKU>"
    And fill "Price Calculation Details Grid Sidebar" with:
      | Website  | Default     |
      | Customer | AmandaRCole |
    And click on <SKU> in grid
    And I should see next prices for "Customer Prices":
      | Item (USD) |
      | 1 <Price>  |

    Examples:
      | SKU   | Price   |
      | PSKU2 | $102.00 |
      | PSKU3 | $103.00 |
      | PSKU4 | $104.00 |
      | PSKU5 | $105.00 |
