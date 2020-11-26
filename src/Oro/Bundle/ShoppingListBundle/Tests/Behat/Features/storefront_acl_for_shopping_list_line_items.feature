@ticket-BB-19079
@fixture-OroShoppingListBundle:ShoppingListFixtureWithCustomers.yml

Feature: Storefront acl for shopping list line items
  In order to check frontstore shopping list line items acl
  As a customer user
  I want to check user permissions

  Scenario: Create different window session
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Set view permission to User
    Given I proceed as the Admin
    And I login as administrator
    And I go to Customers/ Customer User Roles
    And I click edit "Buyer" in grid
    And select following permissions:
      | Shopping List           | View:User | Create:User | Edit:User | Delete:User |
      | Shopping List Line Item | View:User | Create:User | Edit:User | Delete:User |
    When I save form
    Then I should see "Customer User Role has been saved" flash message

  Scenario: Assign Shopping List 5 to customer user Amanda Cole with buyer role
    Given I proceed as the Buyer
    And I login as NancyJSallee@example.org buyer
    And I open page with shopping list Shopping List 5
    When I fill "ShoppingListOwnerForm" with:
      | Customer | Amanda Cole |
    Then I should see 'Shopping list "Shopping List 5" was updated successfully' flash message

  Scenario: Check that Shopping List 5 may be edited by Amanda Cole
    When I login as AmandaRCole@example.org buyer
    And I open page with shopping list Shopping List 5
    When I fill "Shopping List Line Item 1 Form" with:
      | Quantity | 22 |
    Then I should see "Record has been successfully updated" flash message
