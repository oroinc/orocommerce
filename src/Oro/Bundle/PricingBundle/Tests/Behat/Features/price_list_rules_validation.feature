@regression
@postgresql
@ticket-BB-11878
@ticket-BB-20426
@ticket-BB-22609

Feature: Price list rules validation
  In order to use effectively work with price lists
  As an Administrator
  I want to have ability to get information about invalid rules before price list save

  Scenario: Check price list product assignment rule validation
    Given I login as administrator
    And I go to Sales/ Price Lists
    When I click "Create Price List"
    And I fill form with:
      | Name       | Base Price List       |
      | Currencies | US Dollar ($)         |
      | Active     | true                  |
      | Rule       | product.featured == 1 |
    When I save and close form
    Then I should see validation errors:
      | Rule | Invalid expression |
    And I fill form with:
      | Rule | product.featured == true |

  Scenario: Check price list rule quantity expressions validation
    Given I click "Add Price Calculation Rules"
    And I click "Price Calculation Unit Expression Button"
    And I click "Price Calculation Currency Expression Button"
    When I fill "Price Calculation Rules Form" with:
      | Priority | 21474836479 |
    Then I should see "Price Calculation Rules Form" validation errors:
      | Priority | This value should be between -2,147,483,648 and 2,147,483,647. |
    When I fill "Price Calculation Rules Form" with:
      | Price for quantity | product.test.unit         |
      | Calculate As       | pricelist[1].prices.value |
      | Priority           | 1                         |
    And I clear text in "Price Calculation Currency Expression Editor Content"
    And I type "pricelist[1].prices.currency" in "Price Calculation Currency Expression Editor Content"
    And I clear text in "Price Calculation Unit Expression Editor Content"
    And I type "pricelist[1].prices.unit" in "Price Calculation Unit Expression Editor Content"
    And I save and close form
    Then I should see "Field \"test\" is not allowed to be used as \"Quantity\""

  Scenario: Check price list rule calculate as expressions validation
    When I clear text in "Price Calculation Quantity Expression Editor Content"
    And I fill "Price Calculation Rules Form" with:
      | Price for quantity | 1                               |
      | Calculate As       | pricelist[1].prices.value * "a" |
      | Priority           | 1                               |
    And I clear text in "Price Calculation Currency Expression Editor Content"
    And I type "pricelist[1].prices.currency" in "Price Calculation Currency Expression Editor Content"
    And I clear text in "Price Calculation Unit Expression Editor Content"
    And I type "pricelist[1].prices.unit" in "Price Calculation Unit Expression Editor Content"
    And I save and close form
    Then I should see "Price Calculation Rules Form" validation errors:
      | Calculate As | Invalid expression |
    Then I should not see "Field \"test\" is not allowed to be used as \"Quantity\""

  Scenario: Check price list rule condition expressions validation
    When I fill "Price Calculation Rules Form" with:
      | Calculate As | pricelist[1].prices.value |
      | Condition    | product.featured == 1     |
    And I save and close form
    Then I should see validation errors:
      | Condition | Invalid expression |
    And I should not see validation errors:
      | Calculate As | Invalid expression |

  Scenario: Check price list saving with valid rules
    When I fill "Price Calculation Rules Form" with:
      | Condition | product.featured == true |
    And I save and close form
    Then I should see "Price List has been saved" flash message
    And number of records in "Price list Product prices Grid" should be 0

  Scenario: Create dependent price list
    Given I go to Sales/ Price Lists
    When I click "Create Price List"
    And I fill form with:
      | Name       | Dependent Price List |
      | Currencies | US Dollar ($)        |
      | Active     | true                 |
      | Rule       | product.id > 0       |
    And I click "Add Price Calculation Rules"
    And I click "Price Calculation Unit Expression Button"
    And I click on empty space
    And I click "Price Calculation Currency Expression Button"
    And I fill "Price Calculation Rules Form" with:
      | Price for quantity | pricelist[1].prices.quantity    |
      | Calculate As       | pricelist[1].prices.value * 0.5 |
      | Priority           | 1                               |
    And I clear text in "Price Calculation Currency Expression Editor Content"
    And I type "pricelist[1].prices.currency" in "Price Calculation Currency Expression Editor Content"
    And I clear text in "Price Calculation Unit Expression Editor Content"
    And I type "pricelist[1].prices.unit" in "Price Calculation Unit Expression Editor Content"
    And I save and close form

  Scenario: Create featured product
    Given I go to Products/ Products
    When click "Create Product"
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU              | PSKU1  |
      | Name             | PSKU1  |
      | Status           | Enable |
      | Unit Of Quantity | item   |
      | Is Featured      | Yes    |
    And click "AddPrice"
    And fill "Product Price Form" with:
      | Price List | Default Price List |
      | Quantity   | 1                  |
      | Value      | 100                |
      | Currency   | $                  |
    And save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check prices in price lists
    Given I go to Sales/ Price Lists
    And click View Base Price List in grid
    Then number of records in "Price list Product prices Grid" should be 1

  Scenario: Check prices in dependent price list
    Given I go to Sales/ Price Lists
    And click View Dependent Price List in grid
    Then number of records in "Price list Product prices Grid" should be 1
    And I should see following "Price list Product prices Grid" grid:
      | Product SKU | Product Name | Quantity | Unit | Value | Currency | Type      |
      | PSKU1       | PSKU1        | 1        | item | 50.00 | USD      | Generated |

  Scenario: Create product price in default price list
    Given I go to Sales/ Price Lists
    And click View Default Price List in grid
    When I click "Add Product Price"
    And I fill "Add Product Price Form" with:
      | Product  | PSKU1 |
      | Quantity | 2     |
      | Unit     | item  |
      | Price    | 50    |
    And I click "Save"
    Then should see "Product Price has been added" flash message

  Scenario: Check prices in dependent price list after product price creation
    Given I go to Sales/ Price Lists
    And click View Dependent Price List in grid
    Then number of records in "Price list Product prices Grid" should be 2
    And I should see following "Price list Product prices Grid" grid containing rows:
      | Product SKU | Product Name | Quantity | Unit | Value | Currency | Type      |
      | PSKU1       | PSKU1        | 1        | item | 50.00 | USD      | Generated |
      | PSKU1       | PSKU1        | 2        | item | 25.00 | USD      | Generated |

  Scenario: Edit product price in default price list
    Given I go to Sales/ Price Lists
    And click view Default Price List in grid
    And click edit 50.00 in "Price list Product prices Grid"
    And fill "Update Product Price Form" with:
      | Quantity | 3  |
      | Price    | 60 |
    And I click "Save"
    Then should see "Product Price has been added" flash message

  Scenario: Check prices in dependent price list after product price update
    Given I go to Sales/ Price Lists
    And click view Dependent Price List in grid
    Then number of records in "Price list Product prices Grid" should be 2
    And I should see following "Price list Product prices Grid" grid containing rows:
      | Product SKU | Product Name | Quantity | Unit | Value | Currency | Type      |
      | PSKU1       | PSKU1        | 1        | item | 50.00 | USD      | Generated |
      | PSKU1       | PSKU1        | 3        | item | 30.00 | USD      | Generated |

  Scenario: Delete product price in default price list
    Given I go to Sales/ Price Lists
    And click view Default Price List in grid
    And click delete 60.00 in "Price list Product prices Grid"
    And I click "Yes" in confirmation dialogue
    Then should see "Removed" flash message

  Scenario: Check prices in dependent price list after product price deletion
    Given I go to Sales/ Price Lists
    And click view Dependent Price List in grid
    Then number of records in "Price list Product prices Grid" should be 1
    And I should see following "Price list Product prices Grid" grid containing rows:
      | Product SKU | Product Name | Quantity | Unit | Value | Currency | Type      |
      | PSKU1       | PSKU1        | 1        | item | 50.00 | USD      | Generated |
