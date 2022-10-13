@regression
@postgresql
@ticket-BB-11878
@ticket-BB-20426

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
    When I click "Add Price Calculation Rules"
    And I click "Enter expression unit"
    And I click "Enter expression currency"
    And I fill "Price Calculation Rules Form" with:
      | Price for quantity | product.test.unit            |
      | Price Unit         | pricelist[1].prices.unit     |
      | Price Currency     | pricelist[1].prices.currency |
      | Calculate As       | pricelist[1].prices.value    |
      | Priority           | 21474836479                  |
    And I save and close form
    Then I should see validation errors:
      | Price Calculation Quantity | Field "test" is not allowed to be used as "Quantity"           |
      | Rule                       | Invalid expression                                             |
      | Priority                   | This value should be between -2,147,483,648 and 2,147,483,647. |

  Scenario: Check price list rule calculate as expressions validation
    When I fill "Price Calculation Rules Form" with:
      | Price for quantity expression | 1                               |
      | Calculate As                  | pricelist[1].prices.value * "a" |
      | Priority                      | 1                               |
    And I save and close form
    Then I should see validation errors:
      | Calculate As | Invalid expression |
    And I should not see validation errors:
      | Price Calculation Quantity | Field "test" is not allowed to be used as "Quantity |

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
