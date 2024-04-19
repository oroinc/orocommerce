@ticket-BB-21251
@fixture-OroProductBundle:product_with_price.yml
@pricing-storage-combined
Feature: Product price restrictions

  Scenario: Feature background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Add price list restriction to guest customer group
    Given I proceed as the Admin
    And login as administrator
    And go to Customers/ Customer Groups
    And I click Edit Non-Authenticated Visitors in grid
    And fill form with:
      | Fallback | Current customer group only |
    When I save and close form
    Then I should see "Customer group has been saved" flash message

  Scenario: Check prices is not available for guest with restricted price list
    Given I proceed as the Buyer
    And I am on the homepage
    When I type "PSKU1" in "search"
    Then I should see an "Search Autocomplete" element
    And should see "PSKU1" in the "Search Autocomplete Product" element
    And should not see "$10.00" in the "Search Autocomplete Product" element

  Scenario: Add price list restriction to guest customer group 2
    Given I proceed as the Admin
    And login as administrator
    And go to Customers/ Customer Groups
    And I click Edit Group with PriceList in grid
    And fill form with:
      | Fallback    | Current customer group only |
    And fill "Customer Group Form" with:
      | Price List | Default Price List |
    When I save and close form
    Then I should see "Customer group has been saved" flash message

  Scenario: Edit default price list prices
    Given I go to Sales/ Price Lists
    And click View Default Price List in grid
    And click Delete PSKU1 in grid
    And I click "Yes" in confirmation dialogue
    And click Delete PSKU1 in grid
    And I click "Yes" in confirmation dialogue

    And click "Add Product Price"
    When I fill "Add Product Price Form" with:
      | Product  | Product1  |
      | Quantity | 1         |
      | Unit     | item      |
      | Price    | 100       |
    And I click "Save"
    And click "Add Product Price"
    When I fill "Add Product Price Form" with:
      | Product  | Product1  |
      | Quantity | 1         |
      | Unit     | set       |
      | Price    | 200       |
    And I click "Save"

  Scenario: Check prices is not available for customer user with restricted price list.
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on the homepage
    When I type "PSKU1" in "search"
    Then I should see an "Search Autocomplete" element
    And should see "PSKU1" in the "Search Autocomplete Product" element
    And should see "$100.00" in the "Search Autocomplete Product" element

  Scenario: Create price lists
    Given I proceed as the Admin
    And go to Sales/ Price Lists
    And click "Create Price List"
    And I fill "Price List Form" with:
      | Name                  | PL1                  |
      | Currencies            | US Dollar ($)        |
      | Active                | true                 |
      | Rule                  | product.id > 0       |
    And I save and close form
    Then I should see "Price List has been saved" flash message and I close it
    When I click "Add Product Price"
    And fill "Add Product Price Form" with:
      | Product  | Product1 |
      | Quantity | 1        |
      | Unit     | item     |
      | Price    | 10       |
    And click "Save"
    And go to Sales/ Price Lists
    And click "Create Price List"
    And I fill "Price List Form" with:
      | Name                  | PL2                  |
      | Currencies            | US Dollar ($)        |
      | Active                | true                 |
      | Rule                  | product.id > 0       |
    And I save and close form
    Then I should see "Price List has been saved" flash message and I close it
    When I click "Add Product Price"
    And fill "Add Product Price Form" with:
      | Product  | Product1 |
      | Quantity | 1        |
      | Unit     | item     |
      | Price    | 20       |
    And click "Save"

  Scenario: Add price list restriction to customer
    Given I proceed as the Admin
    And go to Customers/ Customers
    And I click edit Group with PriceList in grid
    And fill "Customer Form" with:
      | Fallback     | Current customer only |
      | Price List   | PL1                   |
    And I click "Add Price List"
    And fill "Customer Form" with:
      | Price List2  | PL2                   |
    When I save and close form
    Then I should see "Customer has been saved" flash message

  Scenario: Check product price, should be visible PL1 price
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on the homepage
    When I type "PSKU1" in "search"
    Then I should see an "Search Autocomplete" element
    And should see "PSKU1" in the "Search Autocomplete Product" element
    And should see "$10.00" in the "Search Autocomplete Product" element

  Scenario: Delete PL1 price
    Given I proceed as the Admin
    And I go to Sales/ Price Lists
    And click View PL1 in grid
    And click Delete PSKU1 in grid
    And I click "Yes" in confirmation dialogue

  Scenario: Check product price, should be visible PL2 price
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on the homepage
    When I type "PSKU1" in "search"
    Then I should see an "Search Autocomplete" element
    And should see "PSKU1" in the "Search Autocomplete Product" element
    And should see "$20.00" in the "Search Autocomplete Product" element

  Scenario: Add price to the PL1
    Given I proceed as the Admin
    And I go to Sales/ Price Lists
    And click View PL1 in grid
    And click "Add Product Price"
    When I fill "Add Product Price Form" with:
      | Product  | Product1  |
      | Quantity | 1         |
      | Unit     | item      |
      | Price    | 11        |
    And click "Save"

  Scenario: Check product price, should be visible PL1 price
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I am on the homepage
    When I type "PSKU1" in "search"
    Then I should see an "Search Autocomplete" element
    And should see "PSKU1" in the "Search Autocomplete Product" element
    And should see "$11.00" in the "Search Autocomplete Product" element
