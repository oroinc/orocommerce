@ticket-BB-18293
@fixture-OroShoppingListBundle:ShoppingListFixture.yml
Feature: Shopping List Line Items
  In order to manager shopping lists on front store
  As a Buyer
  I need to be able to update shopping list

  Scenario: Create different window sessions
    Given sessions active:
      | Admin | first_session  |
      | Buyer | second_session |

  Scenario: Merge Line items
    Given I proceed as the Buyer
    And I signed in as AmandaRCole@example.org on the store frontend
    And Buyer is on "Shopping List 5" shopping list
    And I click "Shopping List Actions"
    And I click "Edit"
    And I should see following grid:
      | SKU | Qty Update All |
      | AA1 | 1 set          |
      | AA1 | 2 item         |
    And I click on "Shopping List Line Item 1 Quantity"
    And I fill "Shopping List Line Item Form" with:
      | Quantity | 3    |
      | Unit     | item |
    When I save changes for "Shopping List Line Item 1" row
    Then I should see following grid:
      | SKU | Qty Update All |
      | AA1 | 5 item         |

  Scenario: Check edit permission for line item
    When I proceed as the Admin
    And login as administrator
    And I go to Customers / Customer User Roles
    And I click edit "Buyer" in grid
    And select following permissions:
      | Shopping List Line Item | Edit:None |
    And I save form
    Then I should see "Customer User Role has been saved" flash message

    When I proceed as the Buyer
    And I reload the page
    And I click on "Shopping List Line Item 1 Quantity"
    Then I should not see an "Shopping List Line Item Form" element

   # Because the "delete" action checks acl resource "oro_shopping_list_frontend_update" instead of "oro_shopping_list_line_item_frontend_delete"
  Scenario: Check delete permission for line item
    When I proceed as the Admin
    And select following permissions:
      | Shopping List Line Item | Edit:User (Own) | Delete:None |
    And I save form
    Then I should see "Customer User Role has been saved" flash message

    When I proceed as the Buyer
    And I reload the page
    Then I should see only following actions for row #1 on grid:
      | Add Shopping List item Note |
      | Delete                      |
