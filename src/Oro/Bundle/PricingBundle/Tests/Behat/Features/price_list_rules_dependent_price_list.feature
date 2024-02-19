@regression
@ticket-BB-22609

Feature: Price list rules. Dependent Price List

  Scenario: Create Admin Session
    And I login as administrator

  Scenario: Create Price List based Price List
    When I go to Sales/ Price Lists
    And I click "Create Price List"
    And I fill "Price List Form" with:
      | Name       | PL based       |
      | Currencies | US Dollar ($)  |
      | Active     | true           |
      | Rule       | product.id > 0 |
    And I click "Add Price Calculation Rules"
    When I fill "Price Calculation Rules Form" with:
      | Price for quantity | pricelist[1].prices.quantity   |
      | Price Unit         | pricelist[1].prices.unit       |
      | Price Currency     | pricelist[1].prices.currency   |
      | Calculate As       | pricelist[1].prices.value * 10 |
      | Priority           | 1                              |
    And I save and close form
    Then I should see "Price List has been saved" flash message

  Scenario: Create 2nd level Price List
    Given I go to Sales/ Price Lists
    And click View PL based in grid
    And I remember route parameter "id" value as "pl_price_list_id"
    When I go to Sales/ Price Lists
    And I click "Create Price List"
    And I fill "Price List Form" with:
      | Name       | 2nd level PL   |
      | Currencies | US Dollar ($)  |
      | Active     | true           |
      | Rule       | product.id > 0 |
    And I click "Add Price Calculation Rules"
    When I fill "Price Calculation Rules Form" with:
      | Price for quantity | pricelist[$pl_price_list_id$].prices.quantity   |
      | Price Unit         | pricelist[$pl_price_list_id$].prices.unit       |
      | Price Currency     | pricelist[$pl_price_list_id$].prices.currency   |
      | Calculate As       | pricelist[$pl_price_list_id$].prices.value * 10 |
      | Priority           | 1                                               |
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
      | Price    | 10   |
    And I click "Save"
    Then should see "Product Price has been added" flash message

  Scenario: Check Prices after creation in base Price List
    When I go to Sales/ Price Lists
    And click View PL based in grid
    Then I should see following grid containing rows:
      | Product SKU | Quantity | Unit | Value  | Currency | Type      |
      | SKU1        | 1        | each | 100.00 | USD      | Generated |

  Scenario: Check Prices after creation in dependent Price List
    When I go to Sales/ Price Lists
    And click View 2nd level PL in grid
    Then I should see following grid containing rows:
      | Product SKU | Quantity | Unit | Value    | Currency | Type      |
      | SKU1        | 1        | each | 1,000.00 | USD      | Generated |

  Scenario: Update product price
    When I go to Sales/ Price Lists
    And click View Default Price List in grid
    And click edit 10.00 in "Price list Product prices Grid"
    And fill "Update Product Price Form" with:
      | Price | 5 |
    And I click "Save"
    Then should see "Product Price has been added" flash message

  Scenario: Check Prices after creation in base Price List
    When I go to Sales/ Price Lists
    And click View PL based in grid
    Then I should see following grid containing rows:
      | Product SKU | Quantity | Unit | Value | Currency | Type      |
      | SKU1        | 1        | each | 50.00 | USD      | Generated |

  Scenario: Check Prices after creation in dependent Price List
    When I go to Sales/ Price Lists
    And click View 2nd level PL in grid
    Then I should see following grid containing rows:
      | Product SKU | Quantity | Unit | Value  | Currency | Type      |
      | SKU1        | 1        | each | 500.00 | USD      | Generated |
