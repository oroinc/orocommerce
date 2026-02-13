@regression
@ticket-BB-26592

Feature: Price list rules multi level Dependent Price Lists

  Scenario: Create Admin Session
    Given I login as administrator

  # Create Price Lists without rules to have correct order of autoincrement IDs
  Scenario Outline: Create Price List based Price Lists
    When I go to Sales/ Price Lists
    And I click "Create Price List"
    And I fill "Price List Form" with:
      | Name       | <Name>         |
      | Currencies | US Dollar ($)  |
      | Active     | true           |
      | Rule       | product.id > 0 |
    And I save and close form
    Then I should see "Price List has been saved" flash message
    And I remember ID from current URL as "<Name>.id"

    Examples:
      | Name |
      | PL2  |
      | PL3  |
      | PL4  |
      | PL5  |
      | PL6  |

  # Add rules later because some price list rules with lower IDs may reference price lists with greater IDs
  Scenario Outline: Create Price List Rules
    When I go to Sales/ Price Lists
    And click Edit <Name> in grid
    And I click "Add Price Calculation Rules"
    When I fill "Price Calculation Rules Form" with:
      | Price for quantity    | 1      |
      | Price Unit Static     | each   |
      | Price Currency Static | $      |
      | Calculate As          | <Rule> |
      | Priority              | 1      |
    And I save and close form
    Then I should see "Price List has been saved" flash message

    Examples:
      | Name | Rule                                                                |
      | PL2  | pricelist[1].prices.value*10                                        |
      | PL3  | pricelist[$PL2.id$].prices.value + pricelist[$PL4.id$].prices.value |
      | PL4  | pricelist[$PL2.id$].prices.value*10                                 |
      | PL5  | pricelist[$PL3.id$].prices.value*10                                 |
      | PL6  | pricelist[$PL5.id$].prices.value*10                                 |


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
      | Name | Price     |
      | PL2  | 10.00     |
      | PL3  | 110.00    |
      | PL4  | 100.00    |
      | PL5  | 1,100.00  |
      | PL6  | 11,000.00 |

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
      | Name | Price      |
      | PL2  | 100.00     |
      | PL3  | 1,100.00   |
      | PL4  | 1,000.00   |
      | PL5  | 11,000.00  |
      | PL6  | 110,000.00 |
