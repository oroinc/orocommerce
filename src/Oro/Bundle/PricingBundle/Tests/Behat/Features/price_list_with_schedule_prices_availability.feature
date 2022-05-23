@regression
@ticket-BB-21349
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
@fixture-OroProductBundle:ProductsExportFixture.yml
@fixture-OroPricingBundle:PriceListToProductFixture.yml
@pricing-storage-combined

Feature: Price list with schedule prices availability
  In order to have actual prices
  As an Administrator
  I want to add price lists with schedules to the chain and get prices immediately

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator

  Scenario Outline: Create Price Lists
    When I go to Sales/ Price Lists
    And I click "Create Price List"
    And I fill "Price List Form" with:
      | Name                  | <Price List Name> |
      | Currencies            | US Dollar ($)     |
      | Active                | true              |
      | Activate At (first)   | <Activate At>     |
      | Deactivate At (first) | <Deactivate At>   |
    And I save and close form
    Then I should see "Price List has been saved" flash message

    Given I click "Add Product Price"
    When I fill "Add Product Price Form" with:
      | Product  | PSKU1   |
      | Quantity | 1       |
      | Unit     | item    |
      | Price    | <Price> |
    And I click "Save"
    Then I should see "Product Price has been added" flash message

    Examples:
      | Price List Name | Activate At         | Deactivate At        | Price |
      | ACTIVEPL        | <Date:today -1 day> | <Date:today +1 day>  | 2     |
      | INACTIVEPL      | <Date:today +1 day> | <Date:today +2 days> | 1     |

  Scenario: Assign Price List to Website
    When I go to System/Websites
    And click Edit Default in grid
    And I choose Price List "INACTIVEPL" in 1 row
    And I submit form
    Then I should see "Website has been saved" flash message

  Scenario: Check product price
    Given I proceed as the Buyer
    And I am on the homepage
    And type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "PSKU1"
    And I should see "Your Price: $7.00 / item" for "PSKU1" product

  Scenario: Assign Price List to Website
    Given I proceed as the Admin
    When I click "Add Price List"
    And I choose Price List "ACTIVEPL" in 2 row
    And I submit form
    Then I should see "Website has been saved" flash message

  Scenario: Check product price
    Given I proceed as the Buyer
    And I reload the page
    Then I should see "Your Price: $2.00 / item" for "PSKU1" product
