@regression
@ticket-BB-16555
@fixture-OroSaleBundle:Quote.yml
@fixture-OroSaleBundle:QuoteProductFixture.yml

Feature: Backoffice quote create quote with different price lists

  Scenario: Create window sessions
    Given sessions active:
      | Admin   | first_session  |
    And I proceed as the Admin
    And I login as administrator

  Scenario: Check applied prices for quote
    Given I go to Sales / Price Lists
    And I click edit "first price list" in grid
    Then I fill form with:
      | Currencies | US Dollar ($)  |
    And I save and close form
    Then I click "Add Product Price"
    And I fill "Add Product Price Form" with:
      | Product    | psku1 |
      | Quantity   | 1     |
      | Unit       | item  |
      | Price      | 10    |
    And I click "Save"
    And I click "Add Product Price"
    And I fill "Add Product Price Form" with:
      | Product    | psku1 |
      | Quantity   | 10    |
      | Unit       | item  |
      | Price      | 100   |
    And I click "Save"
    Given I go to Sales / Price Lists
    And I click view "Default Price List" in grid
    And I click "Add Product Price"
    And I fill "Add Product Price Form" with:
      | Product    | psku1 |
      | Quantity   | 1     |
      | Unit       | item  |
      | Price      | 12    |
    And I click "Save"
    Then I go to Customers / Customers
    And I click edit "first customer" in grid
    And fill "Customer Form" with:
      | Price List  | first price list |
    And I save and close form
    Then I go to Sales / Quotes
    And I click "Create Quote"
    And I fill "Quote Form" with:
      | LineItemProduct | psku1 |
    And I click "Tier prices button"
    Then I should see "Click to select price per unit"
    And I should see "$12.00"
    And I fill "Quote Form" with:
      | Customer | first customer |
    And I click "Tier prices button"
    Then I should see "Click to select price per unit"
    And I should see "$100"
    And I should see "$10"

