@regression
@ticket-BB-22609

Feature: Price list rules Attribute dependent Price List

  Scenario: Create Admin Session
    And I login as administrator

  Scenario: Create Price Attributes
    When I go to Products/ Price Attributes
    And I click "Create Price Attribute"
    And I fill form with:
      | Name       | MSRP |
      | Field Name | msrp |
      | Currencies | USD  |
    And I save and close form
    Then I should see "Price Attribute has been saved" flash message

  Scenario: Create Attribute based Price List
    When I go to Sales/ Price Lists
    And I click "Create Price List"
    And I fill "Price List Form" with:
      | Name       | MSRP based     |
      | Currencies | US Dollar ($)  |
      | Active     | true           |
      | Rule       | product.id > 0 |
    And I click "Add Price Calculation Rules"
    When I fill "Price Calculation Rules Form" with:
      | Price for quantity | 1                       |
      | Price Unit         | product.msrp.unit       |
      | Price Currency     | product.msrp.currency   |
      | Calculate As       | product.msrp.value * 10 |
      | Priority           | 1                       |
    And I save and close form
    Then I should see "Price List has been saved" flash message

  Scenario: Create 2nd level attribute based Price List
    Given I go to Sales/ Price Lists
    And click View MSRP based in grid
    And I remember route parameter "id" value as "pl_price_list_id"
    When I go to Sales/ Price Lists
    And I click "Create Price List"
    And I fill "Price List Form" with:
      | Name       | 2nd level PL based |
      | Currencies | US Dollar ($)      |
      | Active     | true               |
      | Rule       | product.id > 0     |
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
    And click "Product Prices"
    Then I should see "Product Price Attribute Unit Each" element inside "Product Price Attribute MSRP Form" element
    When I add price 10 to Price Attribute MSRP
    And I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Check Prices after creation in base Price List
    When I go to Sales/ Price Lists
    And click View MSRP based in grid
    Then I should see following grid containing rows:
      | Product SKU | Quantity | Unit | Value  | Currency | Type      |
      | SKU1        | 1        | each | 100.00 | USD      | Generated |

  Scenario: Check Prices after creation in dependent Price List
    When I go to Sales/ Price Lists
    And click View 2nd level PL based in grid
    Then I should see following grid containing rows:
      | Product SKU | Quantity | Unit | Value    | Currency | Type      |
      | SKU1        | 1        | each | 1,000.00 | USD      | Generated |
