@regression
@ticket-BB-15117
@fixture-OroShoppingListBundle:ProductFixture.yml

Feature: Shopping list without permissions
  In order to disable shopping lists
  As a Sales rep
  I should not see shopping list block without permissions

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |
      | Guest | system_session |

  Scenario: Ensure that shopping list block is visible for user by default
    Given I proceed as the Buyer
    And I login as MarleneSBradley@example.com buyer
    When I am on homepage
    Then I should see "Shopping list"
    When I open product with sku "PSKU1" on the store frontend
    Then I should see "Add to Shopping List"

  Scenario: Ensure that shopping list block didn't visible for guest by default
    Given I proceed as the Guest
    When I am on homepage
    Then I should not see "Shopping list"
    When I open product with sku "PSKU1" on the store frontend
    Then I should not see "Add to Shopping List"

  Scenario: Disable shopping list permissions
    Given I proceed as the Admin
    And I login as administrator
    And I go to Customers/ Customer User Roles
    And I click edit "Buyer" in grid
    And select following permissions:
      | Shopping List | View:None | Create:None | Edit:None | Delete:None | Assign:None | Duplicate:None |
    And I save and close form
    Then I should see "Customer User Role has been saved" flash message

  Scenario: Enable guest shopping lists
    Given I go to System/ Configuration
    When I follow "Commerce/Sales/Shopping List" on configuration sidebar
    Then the "Enable guest shopping list" checkbox should not be checked
    When uncheck "Use default" for "Enable guest shopping list" field
    And I check "Enable guest shopping list"
    And I save setting
    Then I should see "Configuration saved" flash message
    And the "Enable guest shopping list" checkbox should be checked

  Scenario: Check that buyer can see shopping list block and button
    Given I proceed as the Buyer
    When I reload the page
    Then I should not see "Shopping list"
    When I open product with sku "PSKU1" on the store frontend
    Then I should not see "Add to Shopping List"
    When I click "NewCategory"
    Then I should not see "You do not have permission to perform this action." flash message

  Scenario: Ensure that shopping list block didn't visible for guest by default
    Given I proceed as the Guest
    When I reload the page
    Then I should see "Shopping list"
    When I open product with sku "PSKU1" on the store frontend
    Then I should see "Add to Shopping List"
