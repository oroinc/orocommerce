@regression
@ticket-BB-18672
@ticket-BB-20442

Feature: Price list duplicate with rules
  In order to use effectively work with price lists
  As an Administrator
  I want to have ability to duplicate existing price lists that contain rules

  Scenario: Create price list with rules
    Given I login as administrator
    And I go to Sales/ Price Lists
    When I click "Create Price List"
    And I fill form with:
      | Name       | Base Price List |
      | Currencies | US Dollar ($)   |
      | Active     | true            |
      | Rule       | product.id > 0  |
    And I click "Add Price Calculation Rules"
    And I click "Enter expression unit"
    And I click "Enter expression currency"
    And I fill "Price Calculation Rules Form" with:
      | Price for quantity | 1                               |
      | Price Unit         | pricelist[1].prices.unit        |
      | Price Currency     | pricelist[1].prices.currency    |
      | Calculate As       | pricelist[1].prices.value * 1.2 |
      | Condition          | product.featured == true        |
      | Priority           | 1                               |
    And I save and close form
    Then I should see "Price List has been saved" flash message
    And number of records in "Price list Product prices Grid" should be 0

  Scenario: Create price list copy
    Given I reload the page
    When I click "Duplicate Price List"
    Then I should see "Copy of Base Price List"
    And number of records in "Price list Product prices Grid" should be 0
    When I click "Enable"
    Then I should see "Price List was enabled successfully" flash message

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
    And I filter Name as Does Not Contain "Copy"
    And click View Base Price List in grid
    Then number of records in "Price list Product prices Grid" should be 1

    Given I go to Sales/ Price Lists
    And click View Copy of Base Price List in grid
    Then number of records in "Price list Product prices Grid" should be 1
