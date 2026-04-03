@regression
@ticket-BB-21587
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
@fixture-OroShoppingListBundle:ProductFixture.yml

Feature: Shopping list limit create option hidden after add product
  In order to enforce shopping list limits without page reload
  As a Customer User with no shopping lists
  I should not see "Create New Shopping List" option after adding a product when limit is reached

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Set shopping list limit to 1 in configuration
    Given I proceed as the Admin
    And login as administrator
    And I go to System/Configuration
    When I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Shopping List Limit" field
    And I fill in "Shopping List Limit" with "1"
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario: New customer user adds product and Create New Shopping List option is not available
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And I type "PSKU1" in "search"
    And I click "Search Button"
    And I click "View Details" for "PSKU1" product
    And I should see "Add to Shopping List"
    When I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message
    And I click on "Flash Message Close Button"
    When I click "Shopping List Dropdown"
    Then I should not see "Create New Shopping List" in the "ShoppingListButtonGroupMenu" element
