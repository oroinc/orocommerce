@regression
@random-failed
@ticket-BB-17734
@fixture-OroPricingBundle:ProductPricesAudit.yml
Feature: Product prices audit
  In order to have the ability to manage product prices
  As an Administrator
  I want to be able to see the history of product price changes

  Scenario: Feature Background
    Given sessions active:
      | ProductPrice | first_session  |
      | PriceList    | second_session |

  Scenario: Create product price from the product edit page
    Given I switch to the "ProductPrice" session
    And login as administrator
    And go to Products/Products
    And click edit "PSKU1" in grid
    And click "Product Prices"
    And click "Add Product Price"
    When I set Product Price collection element values in 1 row:
      | Price List     | Default Price List |
      | Quantity value | 1                  |
      | Quantity Unit  | each               |
      | Value          | 7.45               |
      | Currency       | $                  |
    And save form
    Then I should see "Product has been saved" flash message

  Scenario: Check created product price from the product edit page
    Given I switch to the "PriceList" session
    And login as administrator
    And go to Sales/Price Lists
    And click view "Default Price List" in grid
    When click "Change History"
    Then should see following "Audit History Grid" grid:
      | Old Values | New Values                                                                                                                                               |
      | Prices:    | Prices:  Product Price "PSKU1 \| 1 each \| 7.4500 USD" added: Currency: USD Price List: Price List "1" Quantity: 1 Unit: Product Unit "each" Value: 7.45 |
    And close ui dialog

  Scenario: Edit product price from the product edit page
    Given I switch to the "ProductPrice" session
    And click "Product Prices"
    When set Product Price collection element values in 1 row:
      | Price List     | Default Price List |
      | Quantity value | 2                  |
      | Quantity Unit  | item               |
      | Value          | 10.45              |
      | Currency       | €                  |
    And save form
    Then should see "Product has been saved" flash message

  Scenario: Check updated product price from the product edit page
    Given I switch to the "PriceList" session
    When click "Change History"
    Then should see following "Audit History Grid" grid:
      | Old Values                                                                                                                         | New Values                                                                                                                                                       |
      | Prices: Product Price "PSKU1 \| 2 item \| 10.4500 EUR" changed: Currency: USD  Quantity: 1  Unit: Product Unit "each"  Value: 7.45 | Prices:  Product Price "PSKU1 \| 2 item \| 10.4500 EUR" changed: Currency: EUR Quantity: 2 Unit: Product Unit "item" Value: 10.45                                |
      | Prices:                                                                                                                            | Prices:  Product Price "PSKU1 \| 1 each \| 7.4500 USD" added: Currency: USD Price List: Price List "1" Quantity: 1 Unit: Product Unit "each" Value: 7.45         |
    And close ui dialog

  Scenario: Delete product price from the product edit page
    Given I switch to the "ProductPrice" session
    And click "Product Prices"
    When remove element in row #1 from Product Price collection
    And save form
    Then I should see "Product has been saved" flash message

  Scenario: Check removed product price from the product edit page
    Given I switch to the "PriceList" session
    When click "Change History"
    Then should see following "Audit History Grid" grid:
      | Old Values                                                                                                                                                    | New Values                                                                                                                                                       |
      | Prices: Product Price "PSKU1 \| 2 item \| 10.4500 EUR" removed: Currency: EUR Price List: Price List "1" Quantity: 2 Unit: Product Unit "item" Value: 10.4500 | Prices:                                                                                                                                                          |
      | Prices: Product Price "PSKU1 \| 2 item \| 10.4500 EUR" changed: Currency: USD  Quantity: 1  Unit: Product Unit "each"  Value: 7.45                            | Prices:  Product Price "PSKU1 \| 2 item \| 10.4500 EUR" changed: Currency: EUR Quantity: 2 Unit: Product Unit "item" Value: 10.45                                |
      | Prices:                                                                                                                                                       | Prices:  Product Price "PSKU1 \| 1 each \| 7.4500 USD" added: Currency: USD Price List: Price List "1" Quantity: 1 Unit: Product Unit "each" Value: 7.45         |
    And close ui dialog

  Scenario: Create product price from the price list view page
    Given I switch to the "ProductPrice" session
    And go to Sales/Price Lists
    And click view "Default Price List" in grid
    And click "Add Product Price"
    When fill "Add Product Price Form" with:
      | Product  | PSKU1 |
      | Quantity | 3     |
      | Unit     | each  |
      | Price    | 5.65  |
      | Currency | $     |
    And click "Save"
    Then I should see "Product Price has been added" flash message

  Scenario: Check created product price from the price list view page
    Given I switch to the "PriceList" session
    When click "Change History"
    Then should see following "Audit History Grid" grid:
      | Old Values                                                                                                                                                    | New Values                                                                                                                                                       |
      | Prices:                                                                                                                                                       | Prices:  Product Price "PSKU1 \| 3 each \| 5.6500 USD" added: Currency: USD Price List: Price List "1" Quantity: 3 Unit: Product Unit "each" Value: 5.65         |
      | Prices: Product Price "PSKU1 \| 2 item \| 10.4500 EUR" removed: Currency: EUR Price List: Price List "1" Quantity: 2 Unit: Product Unit "item" Value: 10.4500 | Prices:                                                                                                                                                          |
      | Prices: Product Price "PSKU1 \| 2 item \| 10.4500 EUR" changed: Currency: USD  Quantity: 1  Unit: Product Unit "each"  Value: 7.45                            | Prices:  Product Price "PSKU1 \| 2 item \| 10.4500 EUR" changed: Currency: EUR Quantity: 2 Unit: Product Unit "item" Value: 10.45                                |
      | Prices:                                                                                                                                                       | Prices:  Product Price "PSKU1 \| 1 each \| 7.4500 USD" added: Currency: USD Price List: Price List "1" Quantity: 1 Unit: Product Unit "each" Value: 7.45         |
    And close ui dialog

  Scenario: Edit product price from the price list view page
    Given I switch to the "ProductPrice" session
    And click "Product Prices"
    And click edit "PSKU1" in grid
    When fill "Update Product Price Form" with:
      | Quantity | 4    |
      | Unit     | item |
      | Price    | 6.75 |
      | Currency | €    |
    And click "Save"
    Then I should see "Product Price has been added" flash message

  Scenario: Check updated product price from the price list view page
    Given I switch to the "PriceList" session
    When click "Change History"
    Then should see following "Audit History Grid" grid:
      | Old Values                                                                                                                                                    | New Values                                                                                                                                                       |
      | Prices: Product Price "PSKU1 \| 4 item \| 6.7500 EUR" changed: Currency: USD  Quantity: 3  Unit: Product Unit "each"  Value: 5.65                             | Prices:  Product Price "PSKU1 \| 4 item \| 6.7500 EUR" changed: Currency: EUR Quantity: 4 Unit: Product Unit "item" Value: 6.75                                  |
      | Prices:                                                                                                                                                       | Prices:  Product Price "PSKU1 \| 3 each \| 5.6500 USD" added: Currency: USD Price List: Price List "1" Quantity: 3 Unit: Product Unit "each" Value: 5.65         |
      | Prices: Product Price "PSKU1 \| 2 item \| 10.4500 EUR" removed: Currency: EUR Price List: Price List "1" Quantity: 2 Unit: Product Unit "item" Value: 10.4500 | Prices:                                                                                                                                                          |
      | Prices: Product Price "PSKU1 \| 2 item \| 10.4500 EUR" changed: Currency: USD  Quantity: 1  Unit: Product Unit "each"  Value: 7.45                            | Prices:  Product Price "PSKU1 \| 2 item \| 10.4500 EUR" changed: Currency: EUR Quantity: 2 Unit: Product Unit "item" Value: 10.45                                |
      | Prices:                                                                                                                                                       | Prices:  Product Price "PSKU1 \| 1 each \| 7.4500 USD" added: Currency: USD Price List: Price List "1" Quantity: 1 Unit: Product Unit "each" Value: 7.45         |
    And close ui dialog

  Scenario: Delete product price from the price list view page
    Given I switch to the "ProductPrice" session
    And click "Product Prices"
    And click delete "PSKU1" in grid
    When click "Yes"
    Then I should see "Removed" flash message

  Scenario: Check removed product price from the price list view page
    Given I switch to the "PriceList" session
    When click "Change History"
    Then should see following "Audit History Grid" grid:
      | Old Values                                                                                                                                                    | New Values                                                                                                                                                       |
      | Prices: Product Price "PSKU1 \| 4 item \| 6.7500 EUR" removed: Currency: EUR Price List: Price List "1" Quantity: 4 Unit: Product Unit "item" Value: 6.7500   | Prices:                                                                                                                                                          |
      | Prices: Product Price "PSKU1 \| 4 item \| 6.7500 EUR" changed: Currency: USD  Quantity: 3  Unit: Product Unit "each"  Value: 5.65                             | Prices:  Product Price "PSKU1 \| 4 item \| 6.7500 EUR" changed: Currency: EUR Quantity: 4 Unit: Product Unit "item" Value: 6.75                                  |
      | Prices:                                                                                                                                                       | Prices:  Product Price "PSKU1 \| 3 each \| 5.6500 USD" added: Currency: USD Price List: Price List "1" Quantity: 3 Unit: Product Unit "each" Value: 5.65         |
      | Prices: Product Price "PSKU1 \| 2 item \| 10.4500 EUR" removed: Currency: EUR Price List: Price List "1" Quantity: 2 Unit: Product Unit "item" Value: 10.4500 | Prices:                                                                                                                                                          |
      | Prices: Product Price "PSKU1 \| 2 item \| 10.4500 EUR" changed: Currency: USD  Quantity: 1  Unit: Product Unit "each"  Value: 7.45                            | Prices:  Product Price "PSKU1 \| 2 item \| 10.4500 EUR" changed: Currency: EUR Quantity: 2 Unit: Product Unit "item" Value: 10.45                                |
      | Prices:                                                                                                                                                       | Prices:  Product Price "PSKU1 \| 1 each \| 7.4500 USD" added: Currency: USD Price List: Price List "1" Quantity: 1 Unit: Product Unit "each" Value: 7.45         |
    And close ui dialog

  Scenario: Change price list of product price from the product edit page
    Given I switch to the "ProductPrice" session
    And go to Products/Products
    And click edit "PSKU1" in grid
    And click "Product Prices"
    And click "Add Product Price"
    When I set Product Price collection element values in 1 row:
      | Price List     | Default Price List |
      | Quantity value | 5                  |
      | Quantity Unit  | each               |
      | Value          | 7.45               |
      | Currency       | $                  |
    And save form
    Then I should see "Product has been saved" flash message
    When I set Product Price collection element values in 1 row:
      | Price List     | Second Price List |
      | Quantity value | 5                 |
      | Quantity Unit  | each              |
      | Value          | 7.45              |
      | Currency       | $                 |
    And save form
    Then I should see "Product has been saved" flash message

  Scenario: Check created product price from the product edit page
    Given I switch to the "PriceList" session
    And go to Sales/Price Lists
    And click view "Default Price List" in grid
    When click "Change History"
    Then should see following "Audit History Grid" grid:
      | Old Values                                                                                                                                                    | New Values                                                                                                                                                       |
      | Prices: Product Price "PSKU1 \| 5 each \| 7.4500 USD" removed: Currency: USD Price List: Price List "1" Quantity: 5 Unit: Product Unit "each" Value: 7.45     | Prices:  Product Price "PSKU1 \| 5 each \| 7.4500 USD" added: Price List: Price List "1"                                                                         |
      | Prices:                                                                                                                                                       | Prices:  Product Price "PSKU1 \| 5 each \| 7.4500 USD" added: Currency: USD Price List: Price List "1" Quantity: 5 Unit: Product Unit "each" Value: 7.45         |
      | Prices: Product Price "PSKU1 \| 4 item \| 6.7500 EUR" removed: Currency: EUR Price List: Price List "1" Quantity: 4 Unit: Product Unit "item" Value: 6.7500   | Prices:                                                                                                                                                          |
      | Prices: Product Price "PSKU1 \| 4 item \| 6.7500 EUR" changed: Currency: USD  Quantity: 3  Unit: Product Unit "each"  Value: 5.65                             | Prices:  Product Price "PSKU1 \| 4 item \| 6.7500 EUR" changed: Currency: EUR Quantity: 4 Unit: Product Unit "item" Value: 6.75                                  |
      | Prices:                                                                                                                                                       | Prices:  Product Price "PSKU1 \| 3 each \| 5.6500 USD" added: Currency: USD Price List: Price List "1" Quantity: 3 Unit: Product Unit "each" Value: 5.65         |
      | Prices: Product Price "PSKU1 \| 2 item \| 10.4500 EUR" removed: Currency: EUR Price List: Price List "1" Quantity: 2 Unit: Product Unit "item" Value: 10.4500 | Prices:                                                                                                                                                          |
      | Prices: Product Price "PSKU1 \| 2 item \| 10.4500 EUR" changed: Currency: USD  Quantity: 1  Unit: Product Unit "each"  Value: 7.45                            | Prices:  Product Price "PSKU1 \| 2 item \| 10.4500 EUR" changed: Currency: EUR Quantity: 2 Unit: Product Unit "item" Value: 10.45                                |
      | Prices:                                                                                                                                                       | Prices:  Product Price "PSKU1 \| 1 each \| 7.4500 USD" added: Currency: USD Price List: Price List "1" Quantity: 1 Unit: Product Unit "each" Value: 7.45         |
    And close ui dialog
    And go to Sales/Price Lists
    And click view "Second Price List" in grid
    When click "Change History"
    Then should see following "Audit History Grid" grid:
      | Old Values                                                                                | New Values                                                                                                                                               |
      | Prices: Product Price "PSKU1 \| 5 each \| 7.4500 USD" removed: Price List: Price List "4" | Prices:  Product Price "PSKU1 \| 5 each \| 7.4500 USD" added: Currency: USD Price List: Price List "4" Quantity: 5 Unit: Product Unit "each" Value: 7.45 |
    And close ui dialog
