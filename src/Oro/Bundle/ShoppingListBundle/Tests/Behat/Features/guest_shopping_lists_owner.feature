@ticket-BB-10050-owner
@fixture-OroCustomerBundle:BuyerCustomerFixture.yml
@fixture-OroShoppingListBundle:ProductFixture.yml
@fixture-OroShoppingListBundle:UserFixture.yml
Feature: Guest shopping lists owner
  As administrator I should have a possibility to change default guest shopping list owner in configuration

  Scenario: Feature Background
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Change default owner to new user
    Given I proceed as the Admin
    And I login as administrator
    When I go to System/Configuration
    And I follow "Commerce/Sales/Shopping List" on configuration sidebar
    And uncheck "Use default" for "Enable Guest Shopping List" field
    And I check "Enable Guest Shopping List"
    And uncheck "Use default" for "Create Guest Shopping Lists Immediately" field
    And I check "Create Guest Shopping Lists Immediately"
    And uncheck "Use default" for "Default Guest Shopping List Owner" field
    And I fill in "Default Guest Shopping List Owner" with "newadmin"
    And I should see "Admin User"
    And I save setting
    Then I should see "Configuration saved" flash message

  Scenario: Create shopping list on frontend
    Given I proceed as the Buyer
    When I am on homepage
    Then I should see "Shopping list"
    When type "PSKU1" in "search"
    And I click "Search Button"
    Then I should see "Product1"
    And I should see "Add to Shopping List"
    When I click "View Details" for "PSKU1" product
    Then I should see "Add to Shopping List"
    When I click "Add to Shopping List"
    Then I should see "Product has been added to" flash message and I close it
    And I should see "In shopping list"

  Scenario: Check shopping list saved with correct owner
    Given I proceed as the Admin
    When I go to Sales/Shopping Lists
    And I click View Shopping List in grid
    Then I should see "Owner: Admin User (Main)"
