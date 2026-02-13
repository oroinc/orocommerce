@regression
@pricing-storage-combined
@ticket-BB-26592
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroProductBundle:ProductsExportFixture.yml

Feature: CPL Recalculation After Price Calculation Rule Update

  Scenario: Create Price List PL1 based on Default price list
    Given I login as administrator
    When I go to Sales/ Price Lists
    And I click "Create Price List"
    And I fill "Price List Form" with:
      | Name       | PL1            |
      | Currencies | US Dollar ($)  |
      | Active     | true           |
      | Rule       | product.id > 0 |
    And I click "Add Price Calculation Rules"
    And I fill "Price Calculation Rules Form" with:
      | Price for quantity | pricelist[1].prices.quantity    |
      | Price Unit         | pricelist[1].prices.unit        |
      | Price Currency     | pricelist[1].prices.currency    |
      | Calculate As       | pricelist[1].prices.value * 0.9 |
      | Priority           | 1                               |
    And I save and close form
    Then I should see "Price List has been saved" flash message

  Scenario: Assign PL1 and Default lists to Customer A with fallback to Current customer only
    When I go to Customers/Customers
    And click Edit AmandaRCole in grid
    And I fill form with:
      | Fallback | Current customer only |
    And I choose Price List "PL1" in 1 row
    And I save and close form
    Then I should see "Customer has been saved" flash message

  Scenario: Create product price in default price list
    Given I go to Sales/ Price Lists
    And click View Default Price List in grid
    When I click "Add Product Price"
    And I fill "Add Product Price Form" with:
      | Product  | PSKU1 |
      | Quantity | 1     |
      | Unit     | item  |
      | Price    | 100   |
    And I click "Save"
    Then should see "Product Price has been added" flash message

  Scenario: Verify prices are generated in PL1 after price creation
    When I go to Sales/ Price Lists
    And click View PL1 in grid
    Then I should see following "Price list Product prices Grid" grid:
      | Product SKU | Quantity | Unit | Value | Currency | Type      |
      | PSKU1       | 1        | item | 90.00 | USD      | Generated |

  Scenario: Open Price Calculation Details and ensure that the prices are set correctly
    When I go to Sales/Price Calculation Details
    And I filter SKU as Contains "PSKU1"
    And fill "Price Calculation Details Grid Sidebar" with:
      | Website  | Default     |
      | Customer | AmandaRCole |
    When I click on PSKU1 in grid
    Then I should see next prices for "Customer Prices":
      | Item (USD) |
      | 1 $90.00   |

  Scenario: Open PL1 and change price calculation rule
    When I go to Sales/ Price Lists
    And click View PL1 in grid
    And I click "Edit"
    And I click "Price Calculation Rules"
    And I fill "Price Calculation Rules Form" with:
      | Calculate As | pricelist[1].prices.value * 0.7 |
    And I save and close form
    Then I should see "Price List has been saved" flash message

  Scenario: Verify prices are generated in PL1 after rule change
    When I go to Sales/ Price Lists
    And click View PL1 in grid
    Then I should see following "Price list Product prices Grid" grid:
      | Product SKU | Quantity | Unit | Value | Currency | Type      |
      | PSKU1       | 1        | item | 70.00 | USD      | Generated |

  Scenario: Check Price calculation details - CPL should be recalculated automatically
    When I go to Sales/Price Calculation Details
    And I filter SKU as Contains "PSKU1"
    And fill "Price Calculation Details Grid Sidebar" with:
      | Website  | Default     |
      | Customer | AmandaRCole |
    When I click on PSKU1 in grid
    Then I should see next prices for "Customer Prices":
      | Item (USD) |
      | 1 $70.00   |
