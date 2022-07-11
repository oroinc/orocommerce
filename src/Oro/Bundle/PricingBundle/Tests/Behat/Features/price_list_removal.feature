@regression
@ticket-BB-21349
@ticket-BB-20952
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
@fixture-OroProductBundle:ProductsExportFixture.yml
@fixture-OroPricingBundle:PriceListToProductFixture.yml
@pricing-storage-combined

Feature: Price list removal
  In order to have actual prices
  As an Administrator
  I want to have ability to remove price list and get old prices back

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Check product price
    Given I proceed as the Buyer
    And I am on the homepage
    And type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "PSKU1"
    And I should see "Your Price: $7.00 / item" for "PSKU1" product

  Scenario: Create Price List
    Given I proceed as the Admin
    And I login as administrator
    When I go to Sales/ Price Lists
    And I click "Create Price List"
    And I fill "Price List Form" with:
      | Name                  | TESTPL              |
      | Currencies            | US Dollar ($)       |
      | Active                | true                |
      | Activate At (first)   | <Date:today -1 day> |
      | Deactivate At (first) | <Date:today +1 day> |
    And I save and close form
    Then I should see "Price List has been saved" flash message

  Scenario: Add product price
    Given I click "Add Product Price"
    When I fill "Add Product Price Form" with:
      | Product  | PSKU1 |
      | Quantity | 1     |
      | Unit     | item  |
      | Price    | 2     |
    And I click "Save"
    Then I should see "Product Price has been added" flash message

  Scenario: Assign Price List to Website
    When I go to System/Websites
    And click Edit Default in grid
    And I choose Price List "TESTPL" in 1 row
    And I submit form
    Then I should see "Website has been saved" flash message

  Scenario: Check product price
    Given I proceed as the Buyer
    And I reload the page
    And I should see "Your Price: $2.00 / item" for "PSKU1" product

  Scenario: Remove Price List
    Given I proceed as the Admin
    When I go to Sales/ Price Lists
    And I click delete "TESTPL" in grid
    And I click "Yes" in confirmation dialogue
    Then I should see "Price list deleted" flash message

  Scenario: Check product price
    Given I proceed as the Buyer
    And I reload the page
    And I should see "Your Price: $7.00 / item" for "PSKU1" product
