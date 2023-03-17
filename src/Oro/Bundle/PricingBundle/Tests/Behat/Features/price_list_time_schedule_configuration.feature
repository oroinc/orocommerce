@fixture-OroPricingBundle:PriceListTimeScheduleConfiguration.yml
@ticket-BB-16591
@pricing-storage-combined

Feature: Price List time schedule configuration

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
    And I proceed as the Admin
    And I login as administrator

  Scenario: Create price list with schedule in the past
    Given go to Sales/ Price Lists
    And click view "Default Price List" in grid
    And I click "Add Product Price"
    And fill "Add Product Price Form" with:
      | Product  | PSKU1 |
      | Quantity | 1     |
      | Unit     | item  |
      | Price    | 5     |
    And click "Save"
    Then I should see "Product Price has been added" flash message
    And go to Sales/ Price Lists
    And click "Create Price List"
    And I fill "Price List Form" with:
      | Name                  | Customer Price List |
      | Currencies            | US Dollar ($)       |
      | Active                | true                |
      | Rule                  | product.id > 0      |
      | Activate At (first)   | <Date:Jul 1, 2017>  |
      | Deactivate At (first) | <Date:Jul 1, 2018>  |
    And I save and close form
    Then I should see "Price List has been saved" flash message and I close it
    When I click "Add Product Price"
    And fill "Add Product Price Form" with:
      | Product  | PSKU1 |
      | Quantity | 1     |
      | Unit     | item  |
      | Price    | 3     |
    And click "Save"
    Then I should see "Product Price has been added" flash message
    When go to Sales/ Price Lists
    Then I should see following grid:
      | Name                 | Status   |
      | Customer Price List  | Inactive |
      | Default Price List   | Active   |
    And price lists scheduled cron processes are executed
    When go to Customers/ Customers
    And click edit "Company A" in grid
    And I click "Add Price List"
    And fill "Customer Form" with:
      | Price List  | Customer Price List |
      | Price List2 | Default Price List  |
    And save and close form
    Then should see "Customer has been saved" flash message

  Scenario: Check that price list with schedule in the past is not available at frontstore
    Given I proceed as the Buyer
    And I login as AmandaRCole@example.org buyer
    When I click "NewCategory"
    Then should not see "Your Price: $3.00 / item" for "PSKU1" product
    And should see "Your Price: $5.00 / item" for "PSKU1" product

  Scenario: Create price list with schedule in the current time period
    Given I proceed as the Admin
    And go to Sales/ Price Lists
    And click edit "Customer Price List" in grid
    And I fill "Price List Form" with:
      | Activate At (first)   | <Date:today>          |
      | Deactivate At (first) | <Date:today +2 month> |
    And I save and close form
    Then I should see "Price List has been saved" flash message and I close it
    When go to Sales/ Price Lists
    Then I should see following grid:
      | Name                | Status |
      | Customer Price List | Active |
      | Default Price List  | Active |
    And price lists scheduled cron processes are executed

  Scenario: Check that price list with schedule in the furure date is not available at frontstore
    Given I proceed as the Buyer
    When I reload the page
    Then should see "Your Price: $3.00 / item" for "PSKU1" product
    And should not see "Your Price: $5.00 / item" for "PSKU1" product

  Scenario: Create price list with schedule in the future
    Given I proceed as the Admin
    And go to Sales/ Price Lists
    And click edit "Customer Price List" in grid
    And I fill "Price List Form" with:
      | Activate At (first)   | <Date:today +1 day>   |
      | Deactivate At (first) | <Date:today +2 month> |
    And I save and close form
    Then I should see "Price List has been saved" flash message and I close it
    When go to Sales/ Price Lists
    Then I should see following grid:
      | Name                | Status   |
      | Customer Price List | Inactive |
      | Default Price List  | Active   |
    And price lists scheduled cron processes are executed

  Scenario: Check that price list with schedule in the current date is available at frontstore
    Given I proceed as the Buyer
    When I reload the page
    Then should see "Your Price: $5.00 / item" for "PSKU1" product
    And should not see "Your Price: $3.00 / item" for "PSKU1" product
