@regression
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
@fixture-OroShoppingListBundle:ProductFixture.yml
Feature: Unable to add hidden product to shopping list

  Scenario: Create different window session
    Given sessions active:
      | Admin     |first_session |
      | Guest     |second_session|

  Scenario: Open product that will be set to hidden
    Given I proceed as the Guest
    And I am on homepage
    And I signed in as AmandaRCole@example.org on the store frontend
    And type "PSKU1" in "search"
    And I click "Search Button"
    And I should see "Product1"
    And I click "Product1"

  Scenario: Set product hidden
    Given I proceed as the Admin
    And I login as administrator
    And I go to Products / Products
    And I click View PSKU1 in grid
    And click "More actions"
    And click "Manage Visibility"
    And fill "Visibility Product Form" with:
      |Visibility To All |hidden |
    And I save form
    Then I should see "Product visibility has been saved" flash message

  Scenario: Unable to add hidden product to shopping list
    Given I proceed as the Guest
    And I click "Add to Shopping List"
    Then I should see "You do not have permission to perform this action." flash message

  Scenario: Set product visible
    Given I proceed as the Admin
    And fill "Visibility Product Form" with:
      |Visibility To All |visible |
    And I save form
    Then I should see "Product visibility has been saved" flash message

  Scenario: Able to add visible product to shopping list
    Given I proceed as the Guest
    And I click "Add to Shopping List"
    And I should see "Product has been added to" flash message and I close it
