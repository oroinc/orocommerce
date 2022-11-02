@ticket-BB-20651
@regression
@fixture-OroCustomerBundle:CustomerUserAmandaRCole.yml

Feature: Shopping list include a product without a price
  As a Buyer
  I need to be able to see add a product without a price in a Shopping list

  Scenario: Feature background
    Given sessions active:
      | User  | first_session  |
      | Admin | second_session |
    Given I proceed as the Admin
    And I login as administrator
    And go to Products/Products
    And click "Create Product"
    When fill form with:
      | Type | Simple |
    And click "Continue"
    And fill "ProductForm" with:
      | SKU    | PSKU1    |
      | Name   | Product1 |
      | Status | Enabled  |
    Then I save and close form
    Then I should see "Product has been saved" flash message

  Scenario: Add product to Shopping List
    Given I operate as the User
    And I login as AmandaRCole@example.org buyer
    And type "PSKU1" in "search"
    And I click "Search Button"
    And I click "View Details" for "PSKU1" product
    And I should see "Add to Shopping List"
    And I click "Add to Shopping List"
    And I should see "Product has been added to" flash message and I close it
    When I follow "Account"
    And I click on "Shopping Lists Navigation Link"
    And I click View "Shopping List" in grid
    And I click on "Open Price Dropdown Button" element in grid row contains "Product1"
    Then I should see "Price for requested quantity is not available"
    When I follow "Account"
    And I click on "Shopping Lists Navigation Link"
    And I click Edit "Shopping List" in grid
    And I click on "Open Price Dropdown Button" element in grid row contains "Product1"
    Then I should see "Price for requested quantity is not available"
