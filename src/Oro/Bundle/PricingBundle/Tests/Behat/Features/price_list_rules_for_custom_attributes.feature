@regression
@ticket-BB-22948
@ticket-BB-19805
@ticket-BB-19761
@fixture-OroProductBundle:single_product_with_category.yml

Feature: Price List Rules for Custom Attributes

  Scenario: Create product attributes
    And I login as administrator

  Scenario Outline: Fill product attributes
    And I go to Products/ Product Attributes
    When I click "Create Attribute"
    And I fill form with:
      | Field Name | <Name> |
      | Type       | <Type> |
    And I click "Continue"
    And I save form
    Then I should see "Attribute was successfully saved" flash message
    And I remember element "Product Attribute Name" value as "field.<Name>"

    Examples:
      | Name               | Type    |
      | use_special_prices | Boolean |
      | base_price         | Float   |
      | base_qty           | Integer |

  Scenario: Add enum product attribute
    And I go to Products/ Product Attributes
    And I click "Create Attribute"
    When I fill form with:
      | Field Name | color  |
      | Type       | Select |
    And I click "Continue"
    And I set Options with:
      | Label |
      | black |
      | white |
    And I save form
    And I remember element "Product Color Attribute White" value as "field.color.white"
    And I remember element "Product Attribute Name" value as "field.color"
    Then I should see "Attribute was successfully saved" flash message

  Scenario: Open category entity management
    Given I go to System / Entities / Entity Management
    And filter Name as is equal to "Category"
    And I click View Category in grid

  Scenario Outline: Create category attributes
    When I click "Create field"
    And I fill form with:
      | Field name   | <Name>    |
      | Storage Type | <Storage> |
      | Type         | <Type>    |
    And I click "Continue"
    And I save and close form
    Then I should see "Field saved" flash message

    Examples:
      | Name             | Storage          | Type    |
      | base_qty         | Table column     | Integer |
      | price_multiplier | Serialized field | Float   |

  Scenario: Create category enum attribute
    When I click "Create field"
    And I fill form with:
      | Field Name   | size         |
      | Type         | Select       |
      | Storage Type | Table column |
    And I click "Continue"
    And I set Options with:
      | Label |
      | s     |
      | m     |
    And I save form
    Then I should see "Field saved" flash message
    And I remember element "Product Size Attribute S" value as "field.size.s"
    And I save and close form

  Scenario: Update schema
    When I click update schema
    Then I should see "Schema updated" flash message

  Scenario: Add product attributes to default family
    Given I go to Products/ Product Families
    When I click "Edit" on row "default_family" in grid
    And I fill "Product Family Form" with:
      | General Attributes | [use_special_prices, base_price, base_qty, color] |
    And I save and close form
    Then I should see "Successfully updated" flash message

  Scenario: Create MSRP Price Attribute
    When I go to Products/ Price Attributes
    When I click "Create Price Attribute"
    And I fill form with:
      | Name       | MSRP |
      | Field Name | msrp |
      | Currencies | USD  |
    And I save and close form
    Then I should see "Price Attribute has been saved" flash message

  Scenario: Create PL1
    Given I go to Sales/ Price Lists
    When I click "Create Price List"
    And I fill form with:
      | Name       | PL1                                                                                                |
      | Currencies | US Dollar ($)                                                                                      |
      | Active     | true                                                                                               |
      | Rule       | product.$field.use_special_prices$ == true and product.category.size == "$field.size.s$" |
    And I click "Add Price Calculation Rules"
    And I fill "Price Calculation Rules Form" with:
      | Price for quantity    | product.$field.base_qty$ |
      | Price Unit Static     | each                     |
      | Price Currency Static | $                        |
      | Calculate As          | 100                      |
      | Priority              | 1                        |
    And I save and close form
    Then should see "Price List has been saved" flash message

  Scenario: Create PL2
    Given I go to Sales/ Price Lists
    When I click "Create Price List"
    And I fill form with:
      | Name       | PL2            |
      | Currencies | US Dollar ($)  |
      | Active     | true           |
      | Rule       | product.id > 0 |
    And I click "Add Price Calculation Rules"
    And I fill "Price Calculation Rules Form" with:
      | Price for quantity    | product.category.base_qty        |
      | Price Unit Static     | each                             |
      | Price Currency Static | $                                |
      | Calculate As          | product.$field.base_price$ * 1.1 |
      | Priority              | 1                                |
    And I save and close form
    Then should see "Price List has been saved" flash message

  Scenario: Create PL3
    Given I go to Sales/ Price Lists
    When I click "Create Price List"
    And I fill form with:
      | Name       | PL3                           |
      | Currencies | US Dollar ($)                 |
      | Active     | true                          |
      | Rule       | product.category.base_qty > 0 |
    And I click "Add Price Calculation Rules"
    And I fill "Price Calculation Rules Form" with:
      | Price for quantity | product.$field.base_qty$                                                                 |
      | Price Unit         | pricelist[1].prices.unit                                                                 |
      | Price Currency     | pricelist[1].prices.currency                                                             |
      | Calculate As       | pricelist[1].prices.value * product.category.price_multiplier                            |
      | Condition          | product.category.price_multiplier > 1 and product.$field.color$ == "$field.color.white$" |
      | Priority           | 1                                                                                        |
    And I save and close form
    Then should see "Price List has been saved" flash message

  Scenario: Create PL4
    Given I go to Sales/ Price Lists
    When I click "Create Price List"
    And I fill form with:
      | Name       | PL4                                   |
      | Currencies | US Dollar ($)                         |
      | Active     | true                                  |
      | Rule       | product.category.price_multiplier > 0 |
    And I click "Add Price Calculation Rules"
    And I fill "Price Calculation Rules Form" with:
      | Price for quantity    | product.category.base_qty                              |
      | Price Unit Static     | each                                                   |
      | Price Currency Static | $                                                      |
      | Calculate As          | product.msrp.value * product.category.price_multiplier |
      | Condition             | product.$field.use_special_prices$ == true             |
      | Priority              | 1                                                      |
    And I save and close form
    Then should see "Price List has been saved" flash message

  Scenario: Fill category extend fields
    When I go to Products/Master Catalog
    And I click "NewCategory"
    And I fill "Category Form" with:
      | base_qty         | 1   |
      | price_multiplier | 1.1 |
      | size             | s   |
    And I save form
    Then I should see "Category has been saved" flash message

  Scenario: Set product attributes
    Given I go to Products/ Products
    And click edit "PSKU1" in grid
    And I add price 1000 to Price Attribute MSRP
    And I fill form with:
      | use_special_prices | Yes   |
      | base_price         | 500   |
      | base_qty           | 10    |
      | color              | white |
    When I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Set default price list prices
    Given I go to Sales/ Price Lists
    And click View Default Price List in grid
    When I click "Add Product Price"
    And I fill "Add Product Price Form" with:
      | Product  | PSKU1 |
      | Quantity | 1     |
      | Unit     | each  |
      | Price    | 100   |
    And I click "Save"
    Then should see "Product Price has been added" flash message
    When I click "Add Product Price"
    And I fill "Add Product Price Form" with:
      | Product  | PSKU1 |
      | Quantity | 10    |
      | Unit     | each  |
      | Price    | 90    |
    And I click "Save"
    Then should see "Product Price has been added" flash message

  Scenario Outline: Check generated prices in dependent price lists
    Given I go to Sales/ Price Lists
    And click View <Price List> in grid
    Then number of records in "Price list Product prices Grid" should be 1
    And I should see following "Price list Product prices Grid" grid:
      | Product SKU | Product Name | Quantity   | Unit | Value   | Currency | Type      |
      | PSKU1       | Product 1    | <Quantity> | each | <Price> | USD      | Generated |

    Examples:
      | Price List | Quantity | Price    |
      | PL1        | 10       | 100.00   |
      | PL2        | 1        | 550.00   |
      | PL3        | 10       | 99.00    |
      | PL4        | 1        | 1,100.00 |

  Scenario: Change category base_qty Table column
    When I go to Products/Master Catalog
    And I click "NewCategory"
    And I fill "Category Form" with:
      | base_qty | 9 |
    And I save form
    Then I should see "Category has been saved" flash message

  Scenario Outline: Check generated prices in dependent price lists
    Given I go to Sales/ Price Lists
    And click View <Price List> in grid
    Then number of records in "Price list Product prices Grid" should be 1
    And I should see following "Price list Product prices Grid" grid:
      | Product SKU | Product Name | Quantity   | Unit | Value   | Currency | Type      |
      | PSKU1       | Product 1    | <Quantity> | each | <Price> | USD      | Generated |

    Examples:
      | Price List | Quantity | Price    |
      | PL1        | 10       | 100.00   |
      | PL2        | 9        | 550.00   |
      | PL3        | 10       | 99.00    |
      | PL4        | 9        | 1,100.00 |

  Scenario: Change category price_multiplier Serialized field
    When I go to Products/Master Catalog
    And I click "NewCategory"
    And I fill "Category Form" with:
      | price_multiplier | 0.5 |
    And I save form
    Then I should see "Category has been saved" flash message

  Scenario Outline: Check generated prices in dependent price lists
    Given I go to Sales/ Price Lists
    And click View <Price List> in grid
    Then number of records in "Price list Product prices Grid" should be 1
    And I should see following "Price list Product prices Grid" grid:
      | Product SKU | Product Name | Quantity   | Unit | Value   | Currency | Type      |
      | PSKU1       | Product 1    | <Quantity> | each | <Price> | USD      | Generated |

    Examples:
      | Price List | Quantity | Price  |
      | PL1        | 10       | 100.00 |
      | PL2        | 9        | 550.00 |
      | PL4        | 9        | 500.00 |

  Scenario: Change no prices for PL3
    When I go to Sales/ Price Lists
    And click View PL3 in grid
    Then there is no records in "Price list Product prices Grid"

  Scenario: Revert category base_qty
    When I go to Products/Master Catalog
    And I click "NewCategory"
    And I fill "Category Form" with:
      | price_multiplier | 1.1 |
    And I save form
    Then I should see "Category has been saved" flash message

  Scenario: Update product use_special_prices Table attribute
    Given I go to Products/ Products
    And click edit "PSKU1" in grid
    And I fill form with:
      | use_special_prices | No |
    When I save and close form
    Then I should see "Product has been saved" flash message

  Scenario Outline: Check generated prices in dependent price lists
    Given I go to Sales/ Price Lists
    And click View <Price List> in grid
    Then number of records in "Price list Product prices Grid" should be 1
    And I should see following "Price list Product prices Grid" grid:
      | Product SKU | Product Name | Quantity   | Unit | Value   | Currency | Type      |
      | PSKU1       | Product 1    | <Quantity> | each | <Price> | USD      | Generated |

    Examples:
      | Price List | Quantity | Price  |
      | PL2        | 9        | 550.00 |
      | PL3        | 10       | 99.00  |

  Scenario Outline: Check generated prices in dependent price lists
    Given I go to Sales/ Price Lists
    And click View <Price List> in grid
    Then there is no records in "Price list Product prices Grid"

    Examples:
      | Price List |
      | PL1        |
      | PL4        |

  Scenario: Revert product use_special_prices Table attribute
    Given I go to Products/ Products
    And click edit "PSKU1" in grid
    And I fill form with:
      | use_special_prices | Yes |
    When I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Update product base_qty Serialized attribute (should be separated from previous one to be sure that serialized attribute updates are processed)
    Given I go to Products/ Products
    And click edit "PSKU1" in grid
    And I fill form with:
      | base_qty | 1 |
    When I save and close form
    Then I should see "Product has been saved" flash message

  Scenario Outline: Check generated prices in dependent price lists
    Given I go to Sales/ Price Lists
    And click View <Price List> in grid
    Then number of records in "Price list Product prices Grid" should be 1
    And I should see following "Price list Product prices Grid" grid:
      | Product SKU | Product Name | Quantity   | Unit | Value   | Currency | Type      |
      | PSKU1       | Product 1    | <Quantity> | each | <Price> | USD      | Generated |

    Examples:
      | Price List | Quantity | Price    |
      | PL1        | 1        | 100.00   |
      | PL2        | 9        | 550.00   |
      | PL3        | 1        | 110.00   |
      | PL4        | 9        | 1,100.00 |

  Scenario: Update product enum attribute
    Given I go to Products/ Products
    And click edit "PSKU1" in grid
    And I fill form with:
      | color | black |
    When I save and close form
    Then I should see "Product has been saved" flash message

  Scenario Outline: Check generated prices in dependent price lists
    Given I go to Sales/ Price Lists
    And click View <Price List> in grid
    Then number of records in "Price list Product prices Grid" should be 1
    And I should see following "Price list Product prices Grid" grid:
      | Product SKU | Product Name | Quantity   | Unit | Value   | Currency | Type      |
      | PSKU1       | Product 1    | <Quantity> | each | <Price> | USD      | Generated |

    Examples:
      | Price List | Quantity | Price    |
      | PL1        | 1        | 100.00   |
      | PL2        | 9        | 550.00   |
      | PL4        | 9        | 1,100.00 |

  Scenario: Change no prices for PL3
    When I go to Sales/ Price Lists
    And click View PL3 in grid
    Then there is no records in "Price list Product prices Grid"

  Scenario: Update category enum
    When I go to Products/Master Catalog
    And I click "NewCategory"
    And I fill "Category Form" with:
      | size | m |
    And I save form
    Then I should see "Category has been saved" flash message

  Scenario Outline: Check generated prices in dependent price lists
    Given I go to Sales/ Price Lists
    And click View <Price List> in grid
    Then number of records in "Price list Product prices Grid" should be 1
    And I should see following "Price list Product prices Grid" grid:
      | Product SKU | Product Name | Quantity   | Unit | Value   | Currency | Type      |
      | PSKU1       | Product 1    | <Quantity> | each | <Price> | USD      | Generated |

    Examples:
      | Price List | Quantity | Price    |
      | PL2        | 9        | 550.00   |
      | PL4        | 9        | 1,100.00 |

  Scenario Outline: Check generated prices in dependent price lists
    Given I go to Sales/ Price Lists
    And click View <Price List> in grid
    Then there is no records in "Price list Product prices Grid"

    Examples:
      | Price List |
      | PL1        |
      | PL3        |
