@regression
@fixture-OroShoppingListBundle:ShoppingListFixture.yml
Feature: Inline Editing
  As Administrator I have a possibility to restrict inline editing for shopping list

  Scenario: Create different window session
    Given sessions active:
      | User  | first_session  |
      | Admin | second_session |

  Scenario: Edit shopping list title
    Given I proceed as the User
    And I login as AmandaRCole@example.org buyer
    And Buyer is on "Shopping List 1" shopping list
    When I click "Edit Shopping List Label"
    And I type "Shopping List 2" in "value"
    And I click "Save"
    Then I should see "Record has been successfully updated" flash message

  Scenario: Deny edit shopping list
    Given I proceed as the Admin
    And I login as administrator
    And I go to Customers/ Customer User Roles
    And I click edit "Buyer" in grid
    And select following permissions:
      | Shopping List | Edit:None |
    And I save and close form
    Then I should see "Customer User Role has been saved" flash message

  Scenario: Check that buyer can't change shopping list title
    Given I proceed as the User
    When Buyer is on "Shopping List 2" shopping list
    Then I should not see following buttons:
      | Edit Shopping List Label |
