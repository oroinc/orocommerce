@regression
@pricing-storage-combined
@ticket-BB-26592
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml
@fixture-OroProductBundle:ProductsExportFixture.yml

Feature: CPL processing with new generated prices

  Scenario: Create Price List
    And I login as administrator
    When I go to Sales/ Price Lists
    And I click "Create Price List"
    And I fill "Price List Form" with:
      | Name       | TESTPL                         |
      | Currencies | US Dollar ($)                  |
      | Active     | true                           |
      | Rule       | pricelist[1].prices.value > 10 |
    And I click "Add Price Calculation Rules"
    And I fill "Price Calculation Rules Form" with:
      | Price for quantity    | 1    |
      | Price Unit Static     | item |
      | Price Currency Static | $    |
      | Calculate As          | 100  |
      | Priority              | 1    |
    And I save and close form
    Then I should see "Price List has been saved" flash message

  Scenario: Assign Price List to customer
    When I go to Customers/Customers
    And click Edit AmandaRCole in grid
    And I fill form with:
      | Fallback | Current customer only |
    And I choose Price List "TESTPL" in 1 row
    And I save and close form
    Then I should see "Customer has been saved" flash message

  Scenario: Create product price in default price list with value less than in rule
    Given I go to Sales/ Price Lists
    And click View Default Price List in grid
    When I click "Add Product Price"
    And I fill "Add Product Price Form" with:
      | Product  | PSKU1 |
      | Quantity | 1     |
      | Unit     | item  |
      | Price    | 10    |
    And I click "Save"
    Then should see "Product Price has been added" flash message

  Scenario: Check PSKU1 was not added to CPL that is based on generated price list
    When I go to Sales/Price Calculation Details
    And I filter SKU as Contains "PSKU1"
    And fill "Price Calculation Details Grid Sidebar" with:
      | Website  | Default     |
      | Customer | AmandaRCole |
    And click on PSKU1 in grid
    Then I should see "Customer Prices No Prices"

  Scenario: Edit product price in default price list set price value matching price rule
    Given I go to Sales/ Price Lists
    And click View Default Price List in grid
    And click edit PSKU1 in "Price list Product prices Grid"
    And fill "Update Product Price Form" with:
      | Price | 110 |
    And I click "Save"
    Then should see "Product Price has been added" flash message

  Scenario: Check price available in CPL that is based on generated price list
    When I go to Sales/Price Calculation Details
    And I filter SKU as Contains "PSKU1"
    And fill "Price Calculation Details Grid Sidebar" with:
      | Website  | Default     |
      | Customer | AmandaRCole |
    And click on PSKU1 in grid
    And I should see next prices for "Customer Prices":
      | Item (USD) |
      | 1 $100.00  |

  Scenario: Edit product price in default price list set value less than in rule
    Given I go to Sales/ Price Lists
    And click View Default Price List in grid
    And click edit PSKU1 in "Price list Product prices Grid"
    And fill "Update Product Price Form" with:
      | Price | 5 |
    And I click "Save"
    Then should see "Product Price has been added" flash message

  Scenario: Check PSKU1 was removed from CPL that is based on generated price list
    When I go to Sales/Price Calculation Details
    And I filter SKU as Contains "PSKU1"
    And fill "Price Calculation Details Grid Sidebar" with:
      | Website  | Default     |
      | Customer | AmandaRCole |
    And click on PSKU1 in grid
    Then I should see "Customer Prices No Prices"
