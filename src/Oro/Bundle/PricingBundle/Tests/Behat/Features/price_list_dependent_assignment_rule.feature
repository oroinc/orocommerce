@regression
@ticket-BB-26592

Feature: Price list dependent assignment rule

  Scenario: Create Admin Session
    Given I login as administrator

  Scenario: Create Price List based Price List
    When I go to Sales/ Price Lists
    And I click "Create Price List"
    And I fill "Price List Form" with:
      | Name       | PL1            |
      | Currencies | US Dollar ($)  |
      | Active     | true           |
      | Rule       | product.id > 0 |
    And I click "Add Price Calculation Rules"
    And I fill "Price Calculation Rules Form" with:
      | Price for quantity    | 1                         |
      | Price Unit Static     | each                      |
      | Price Currency Static | $                         |
      | Calculate As          | pricelist[1].prices.value |
      | Priority              | 1                         |
    And I save and close form
    Then I should see "Price List has been saved" flash message
    And I remember ID from current URL as "PL1.id"

  Scenario: Create Price List with assignment rule dependent on generated Price List
    When I go to Sales/ Price Lists
    And I click "Create Price List"
    And I fill "Price List Form" with:
      | Name       | PL2                                  |
      | Currencies | US Dollar ($)                        |
      | Active     | true                                 |
      | Rule       | pricelist[$PL1.id$].prices.value > 0 |
    And I click "Add Price Calculation Rules"
    And I fill "Price Calculation Rules Form" with:
      | Price for quantity    | 1                                |
      | Price Unit Static     | each                             |
      | Price Currency Static | $                                |
      | Calculate As          | pricelist[$PL1.id$].prices.value |
      | Priority              | 1                                |
    And I save and close form
    Then I should see "Price List has been saved" flash message

  Scenario: Create product
    When I go to Products/ Products
    And I click "Create Product"
    And click "Continue"
    And fill "Create Product Form" with:
      | SKU    | SKU1     |
      | Name   | Product1 |
      | Status | Enabled  |
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Create product price
    When I go to Sales/ Price Lists
    And click View Default Price List in grid
    And I click "Add Product Price"
    And I fill "Add Product Price Form" with:
      | Product  | SKU1 |
      | Quantity | 1    |
      | Unit     | each |
      | Price    | 1    |
    And I click "Save"
    Then should see "Product Price has been added" flash message

  Scenario Outline: Check Dependent Prices after base price creation
    When I go to Sales/ Price Lists
    And click View <Name> in grid
    Then I should see following grid containing rows:
      | Product SKU | Quantity | Unit | Value   | Currency | Type      |
      | SKU1        | 1        | each | <Price> | USD      | Generated |

    Examples:
      | Name | Price |
      | PL1  | 1.00  |
      | PL2  | 1.00  |

  Scenario: Update product price
    When I go to Sales/ Price Lists
    And click View Default Price List in grid
    And click edit SKU1 in "Price list Product prices Grid"
    And fill "Update Product Price Form" with:
      | Price | 10 |
    And I click "Save"
    Then should see "Product Price has been added" flash message

  Scenario Outline: Check Dependent Prices after base price creation
    When I go to Sales/ Price Lists
    And click View <Name> in grid
    Then I should see following grid containing rows:
      | Product SKU | Quantity | Unit | Value   | Currency | Type      |
      | SKU1        | 1        | each | <Price> | USD      | Generated |

    Examples:
      | Name | Price |
      | PL1  | 10.00 |
      | PL2  | 10.00 |
